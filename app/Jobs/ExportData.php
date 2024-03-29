<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Facades\Storage;
use Auth;
use App\Models\Measurement;
use App\Models\Taxon;
use App\Models\Individual;
use App\Models\Location;
use App\Models\Voucher;
use Log;

class ExportData extends AppJob
{

    /**
     * Execute the job.
     *
     * @return void
     */
     public function inner_handle()
     {

         $data =  $this->extractEntrys();

         /*$tolog = implode("|",array_keys($data));
         $this->appendLog($tolog);
         $tolog = implode("|",array_values($data));
         $this->appendLog($tolog);
         */

         //which model to export
         $object_type = $data['object_type'];
         //which API endpoint to use?
         if ($object_type == 'Biocollection') {
           $endpoint = 'biocollections';
         } else {
           $endpoint = strtolower($object_type)."s";
         }
         $base_uri = env("APP_DOCKER_URL")."/api/v0/";
         #$base_uri = "http://172.17.0.1/api/v0/";
         //records to export
         //if user selected records, this is set
         $params = [];
         $export_ids = [];
         if (isset($data['export_ids'])) {
           $export_ids = explode(",",$data['export_ids']);
         } else {
           //if none informed get the scope from the list and export all that apply
           if (isset($data['project'])) {
             $params['project'] = $data['project'];
           }
           if (isset($data['dataset'])) {
             $params['dataset'] = $data['dataset'];
           }
           if (isset($data['measured_type'])) {
             $params['measured_type'] = $data['measured_type'];
           }
           if (isset($data['trait'])) {
             $params['trait'] = $data['trait'];
           }
           if (isset($data['taxon_root'])) {
             $params['taxon_root'] = $data['taxon_root'];
           } elseif (isset($data['taxon'])) {
             $params['taxon'] = $data['taxon'];
           }
           if (isset($data['location_root'])) {
             $params['location_root'] = $data['location_root'];
           }
           if (isset($data['individual'])) {
             $params['individual'] = $data['individual'];
           }
           if (isset($data['voucher'])) {
             $params['voucher'] = $data['voucher'];
           }
           //get the ids for the query (this will speed up small queries)
           $client = new Guzzle(['base_uri' => $base_uri]);
           $params['fields'] = 'id';
           try {
              $response = $client->request('GET', $endpoint, [
                  'query' => $params,
                  'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => Auth::user()->api_token,
                  ],
              ]);
           } catch (ClientException $e) {
              $response = null;
           }
           if (null !== $response and 200 == $response->getStatusCode()) {
             $export_ids  =  json_decode($response->getBody())->data;
           }
         }


         //$this->appendLog("ATENCAO>".implode("|",$export_ids));
         if (count($export_ids)>0) {
           //chunk the records and get the response for the respective api
           $chunks = array_chunk($export_ids,500);

           //set progress
           //will log progress on each chunk
           $this->setProgressMax($chunks);

           //create temporary file to store the data (add the job id to the file name to be able to destroy it)
           $jobid = $this->userjob->id;
           $filename = "job-".$jobid."_".uniqid().".".$data['filetype'];
           $path = 'app/public/downloads/'.$filename;
           $writer = SimpleExcelWriter::create(storage_path($path));

           //for each chunk get data from the api
           $counter = 1;
           $categories = [];
           foreach($chunks as $ids) {
              //if user cancels job
              if ($this->isCancelled()) {
                break;
              }


              $fields = $data['fields'];
              $params = array('fields' => $fields, 'id' => implode(',',$ids));

              $client = new Guzzle(['base_uri' => $base_uri]);
              try {
                 $response = $client->request('GET', $endpoint, [
                     'query' => $params,
                     'headers' => [
                       'Accept' => 'application/json',
                       'Authorization' => Auth::user()->api_token,
                     ],
                 ]);
              } catch (ClientException $e) {
                 //FAILED (sometimes is just memory_limit restriction)
                 break;
              }
              if (200 != $response->getStatusCode()) {
                  break;
              } // FAILED

              $this->userjob->tickProgress();


              //get response and save to file
              if (null !== $response) {
                $answer = json_decode($response->getBody(),true)['data'];
                if ($endpoint=='traits') {
                  $newvalues = [];
                  foreach ($answer as $key => $value) {
                     if (!empty($value['categories'])) {
                       $categories[$value['export_name']] = $value['categories'];
                     }
                     unset($value['categories']);
                     $newvalues[] = $value;
                  }
                  $writer->addRows($newvalues);
                } else {
                  $writer->addRows($answer);
                }
              }

              //limit the number of requests per minute (rest for a minute if greater than 60)
              if ($counter == 59) {
                sleep(60);
                $counter=1;
              } else {
                $counter++;
              }

           }

           //LOG THE FILE FOR USER DOWNLOAD
           $today = now();
           $file = "This file contains your requested export for <strong>".$object_type."</strong> prepared ".$today." ";
           $tolog = $file."<br> <a href='".url('storage/downloads/'.$filename)."' download >".$filename."</a>";

           if (count($categories)) {
             $filename2 = "job-".$jobid."_".uniqid()."_Categories.".$data['filetype'];
             $path2 = 'app/public/downloads/'.$filename2;
             $writer2 = SimpleExcelWriter::create(storage_path($path2));
             foreach($categories as $export_name => $cats) {
               foreach($cats as $cat) {
                  $cat = array_merge(['export_name'=>$export_name],$cat);
                  $writer2->addRow($cat);
               }
             }
             $tolog = $tolog."<br>This file contains the categories for included traits: <a href='".url('storage/downloads/'.$filename2)."' download >".$filename2."</a>";
          }
          $this->appendLog($tolog);


        } else {
          $this->appendLog("Nothing to export or you don't have permissions");
        }
 }

}
