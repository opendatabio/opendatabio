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

         //which model to export
         $object_type = $data['object_type'];

         //records to export
         if (isset($data['export_ids'])) {
           $export_ids = explode(",",$data['export_ids']);
         } else {
           //if none informed export all for which  the user has access for
           $object = app($object_type);
           $export_ids = $object->cursor()->pluck('id')->toArray();
         }

         //which API endpoint to use?
         if ($object_type == 'Herbarium') { $endpoint = 'herbaria'; } else {
           $endpoint = strtolower($object_type)."s";
         }
         $base_uri = env("APP_URL")."api/v0/";


         //chunk the records and get the response for the respective api
         $chunks = array_chunk($export_ids,100);
         //will log progress on each chunk

         //set progress
         $this->setProgressMax($chunks);

         //create temporary file to store the data (add the job id to the file name to be able to destroy it)
         $jobid = $this->userjob->id;
         $filename = "job-".$jobid."_".uniqid().".".$data['filetype'];
         $path = 'downloads_temp/'.$filename;
         $writer = SimpleExcelWriter::create(public_path($path));


         //for each chunk get data from the api
         foreach($chunks as $ids) {
            //if user cancels job
            if ($this->isCancelled()) {
              break;
            }

            $this->userjob->tickProgress();

            //api response (will be a json formated data)
            $fields = $data['fields'];
            $params = array('id'=>implode(',',$ids),'fields' => $fields);


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
                break; //FAILED
            }
            if (200 != $response->getStatusCode()) {
                break;
            } // FAILED

            //get response and save to file
            $answer = json_decode($response->getBody())->data;
            foreach($answer as $line)
            {
                $row = (array)$line;
                $writer->addRow($row);
            }
         }

         //LOG THE FILE FOR USER DOWNLOAD
         $today = now();
         $file = "Your requested export for <strong>".$object_type."</strong> prepared ".$today." ";
         $tolog = $file."<br><a href='".url('downloads_temp/'.$filename)."' download >".$filename."</a>";
         $this->appendLog($tolog);


    }

}
