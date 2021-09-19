<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\Project;
use App\Models\Measurement;
use App\Models\Individual;
use App\Models\Voucher;
use App\Models\Taxon;
use App\Models\Location;
use App\Models\Media;
use App\Models\ODBFunctions;
use App\Models\ODBTrait;
use Spatie\SimpleExcel\SimpleExcelWriter;
use File;
use Storage;
use Illuminate\Http\Request;
use DB;
use Auth;
use Lang;
use Mail;
use Activity;
use ZipArchive;
use App\Http\Api\v0\Controller;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;

//use App\ActivityFunctions;
use Illuminate\Support\Str;


class DownloadDataset extends AppJob
{

    /**
     * Execute the job.
     *
     * @return void
     */
     public function inner_handle()
     {


        $data =  $this->extractEntrys();

        $jobid = "job-".$this->userjob->id;

        //get all ids
        $dataset = Dataset::findOrFail($data['id']);
        $individuals_ids = $dataset->all_individuals_ids();
        $vouchers_ids = $dataset->all_voucher_ids();
        $measurements_ids = $dataset->measurements()->pluck('id')->toArray();
        $locations_ids = $dataset->all_locations_ids();
        $taxons_ids = $dataset->all_taxons_ids();
        $media_ids = $dataset->media()->pluck('id')->toArray();
        $odbtrait_ids = $dataset->measurements()->pluck('trait_id')->toArray();

        $export_set = [
          'individuals' => $individuals_ids,
          'vouchers' => $vouchers_ids,
          'measurements' => $measurements_ids,
          'taxons' => $taxons_ids,
          'locations' => $locations_ids,
          'media' => $media_ids,
          'traits' => $odbtrait_ids,
        ];
        $export_set = array_filter($export_set,function($set) { return count($set)>0;});

        if (count($export_set)==0) {
          $this->appendLog('Error: there is nothing to be exported THE ID IS'.$data['id']);
          return false;
        }
        $chunked_set = [];
        $nsteps =0;
        foreach($export_set as $endpoint => $ids ) {
            $ids = array_chunk($ids,500);
            $nsteps = $nsteps+count($ids);
            $chunked_set[$endpoint] = $ids;
        }
        $nsteps=$nsteps+2+count($media_ids);
        $this->userjob->setProgressMax($nsteps);
        $files = [];
        foreach($chunked_set as $endpoint => $chunks) {
              $filename = $this->saveModel($endpoint,$chunks);
              $files[] = $filename;
        }


        /* bibtex file */
        $references = $dataset->references();
        if ($references->count()>0) {
          $filename = "job-".$jobid."_DatasetBibliography.bib";
          $path = 'app/public/downloads/'.$filename;
          $text = '';
          foreach($references->cursor() as $reference)
          {
            $text .= $reference->bibtex."\n\n";
          }
          $files[] = $filename;
          $fn = fopen(storage_path($path),'w');
          fwrite($fn,$readme);
          fclose($fn);
        }


        $dataset = Dataset::find($data['id']);
        $public_dir=storage_path('app/public/downloads');
        $today = now();
        $datasetname = $dataset->name;

        /*media files */
        // TODO: MUST LIMIT SIZE

        if (count($media_ids)>0) {
          $jobid = "job-".$this->userjob->id;
          $mediazipfilename = $jobid."_MediaFiles_".$today->toDateString().".zip";
          $mediazipfile =  $public_dir . '/' . $mediazipfilename;

          // Create ZipArchive Obj
          $mediazip = new ZipArchive;
          if (true === ($mediazip->open($mediazipfile, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
            // Add Files in ZipArchive
            $medias = Media::whereIn("id",$media_ids)->cursor();
            foreach($medias as $media) {
                $newname = $media->file_name;
                $mediazip->addFile($media->getPath(),$newname);
            }
            $mediazip->close();
         }
       }

        // TODO: REPACE BY MARDOWN
        /* ADD README TO FILE PACK WITH DATASET DETAILS AND POLICIES */
        $readme = "\n==========README=========\n";
        $readme .= Lang::get('messages.dataset').": ".$dataset->name."\n";
        $readme .= Lang::get('messages.downloaded')." - ".now()." - ";
        $readme .= "URL: ".url('dataset/'.$dataset->id)."\n";
        $readme .= "\n================\n";
        $readme .= $dataset->description;
        $readme .= "\n\n================\n";

        $readme .= Lang::get('messages.howtocite').":\n";
        $readme .= $dataset->bibliographicCitation."\n\n";
        $readme .= $dataset->bibtex."\n\n";
        $readme .= "\n\n================\n";

        if ($dataset->license) {
          $readme .=  Lang::get('messages.license').":\n".$dataset->license;
        }
        if ($dataset->policy) {
          $readme .=  "\nSome restrictions may apply. ".Lang::get('messages.data_policy').":\n".$dataset->policy;
        }
        $readme .= "\n\n================\n";

        $mandatory = $dataset->references()->where('mandatory',1);
        if ($mandatory->count()) {
          $readme .=  Lang::get('messages.dataset_bibreferences_mandatory').": \n";
          foreach($mandatory->cursor() as $reference)
          {
            $readme .=  $reference->first_author." ".$reference->year.". ".$reference->title."\n\n";
            $readme .=  $reference->bibtex."\n\n";
          }
          $readme .= "\n\n================\n";
        }

        /* who are the administrators */
        $readme .= Lang::get('messages.admins').":\n";
        foreach($dataset->admins as $admin) {
          if ($admin->person) {
            $adm = $admin->person->fullname." - ".$admin->email;
          } else {
            $adm = $admin->email;
          }
          $readme .= "\t".$adm."\n";
        }
        $readme .= "\n\n================\n";

        if ($dataset->project) {
          $readme .= Lang::get('messages.ṕroject').":\n";
          $title = isset($dataset->project->title) ? $dataset->project->title : $dataset->project->name;
          $readme .= "\t".$title."\n\tURL: ".url("projects/".$dataset->project->id)."\n";
        }

        $readme .=  Lang::get('messages.files').": \n";
        foreach($files as $file) {
          $readme .=  $file." \n";
        }
        $readme .= "\n\n================\n";
        $filename = "job-".$jobid."_README.txt";
        $files[] = $filename;
        $path = 'app/public/downloads/'.$filename;
        $fn = fopen(storage_path($path),'w');
        fwrite($fn,$readme);
        fclose($fn);


        //ZIP THE FILES INTO A SINGLE BUNDLE
        // Define Dir Folder


        // Zip File Name
        $zipFileName = Str::ascii($datasetname);
        $zipFileName = str_replace("  "," ",$zipFileName);
        $zipFileName = str_replace(" ","_",$zipFileName);
        $zipFileName = $zipFileName."_".$today->toDateString().".zip";

        //add job id to name to find and delete when user delete his job.
        $jobid = "job-".$this->userjob->id;
        $zipFileName = $jobid."_".$zipFileName;

        // Create ZipArchive Obj
        $zip = new ZipArchive;
        $zipfile =  $public_dir . '/' . $zipFileName;
        if (true === ($zip->open($zipfile, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
            // Add Files in ZipArchive
            foreach($files as $file) {
                $newname = explode("_",$file);
                unset($newname[0]);
                $newname = implode("_",$newname);
                $zip->addFile($public_dir."/".$file,$newname);
            }
            $zip->close();

            //delete files
            foreach($files as $file) {
                $pathToDel = $public_dir."/".$file;
                File::delete($pathToDel);
            }
        }

        //LOG THE FILE FOR USER DOWNLOAD
        $file = "Files for dataset <strong>".$datasetname."</strong> prepared ".$today." ";
        $tolog = $file."<br><a href='".url('storage/downloads/'.$zipFileName)."' download >".$zipFileName."</a>";

        if (count($media_ids)>0) {
          $tolog .= "<br><a href='".url('storage/downloads/'.$mediazipfilename)."' download >".$mediazipfilename."</a>";
        }

        $tolog .= "<br>".Lang::get('messages.dataset_download_file_tip');
        $this->appendLog($tolog);


        //log dataset export for tracking download and use history
        $logName  = 'dataset_exports';
        $tolog = [
            'attributes' => [
              'dataset_id' => $data['id'],
              'user_id' => Auth::user()->id
            ],
            'old' => NULL
          ];
        activity($logName)
          ->performedOn(Dataset::find($data['id']))
          ->withProperties($tolog)
          ->log('Authorized download');
        $this->userjob->progress = $this->userjob->progress_max;
        $this->userjob->save();

        //send email to user
        if (null != env('MAIL_USERNAME')) {
          $to_email = Auth::user()->email;
          if (isset(Auth::user()->person->full_name)) {
            $to_name = Auth::user()->person->full_name;
          } else {
            $to_name = $to_email;
          }
          $content = Lang::get('messages.dataset_downloaded_message').":<br>".$zipFileName;
          if (isset($mediazipfilename)) {
            $content .= "<br>".$mediazipfilename;
          }
          $content .= "<br><br>".Lang::get('messages.dataset').":  &nbsp;<strong>".$datasetname."</strong> [".now().",  @ Job Id# ".$this->userjob->id."].";
          $content .= "<br>URL:&nbsp;<a href='".env('APP_URL')."/userjobs/".$this->userjob->id."'>";
          $content .= env('APP_URL')."/userjobs/".$this->userjob->id."</a><br>";
          $content .= "<br>".Lang::get('messages.dataset_download_file_tip');
          $data = [
            'to_name' => $to_name,
            'content' => $content,
          ];
          try {
            Mail::send('common.email', $data, function($message) use ($to_name, $to_email) {
              $message->to($to_email, $to_name)->subject(Lang::get('messages.dataset_request').' - '.env('APP_NAME'));
            });
          } catch (\Exception $e) {
            $tolog = "Error: Could not send email";
            $this->appendLog($tolog);
          }
        }
      }

/////////////////////////
    public function getDW($endpoint)
    {
        switch ($endpoint) {
            case 'individuals':
              return "Organisms";
              break;
            case 'individuallocations':
              return "Occurrences";
              break;
            case 'vouchers':
              return "PreservedSpecimens";
              break;
            case 'taxons':
              return "Taxons";
              break;
            case 'measurements':
              return "MeasurementsOrFacts";
              break;
            case 'traits':
              return "MeasurementTypes";
              break;
            case 'media':
              return "MediaAttributes";
              break;
            case 'locations':
              return "Locations";
              break;
          default:
            return null;
            break;
        }
    }

    public function saveModel($endpoint,$chunks)
    {
       //create temporary file to store the data (add the job id to the file name to be able to destroy it)
       $jobid = $this->userjob->id;
       $filename = "job-".$jobid."_".self::getDW($endpoint).".csv";
       $path = 'app/public/downloads/'.$filename;
       $writer = SimpleExcelWriter::create(storage_path($path));

       $base_uri = env("APP_DOCKER_URL")."/api/v0/";

       $counter = 1;
       foreach($chunks as $ids) {
          //if user cancels job
          if ($this->isCancelled()) {
            break;
          }
          $fields = "all";
          if ($endpoint=='traits') {
            $fields = "exceptcategories";
          }
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
            $writer->addRows($answer);
          }
          //limit the number of requests per minute (rest for a minute if greater than 60)
          if ($counter == 59) {
            sleep(60);
            $counter=1;
          } else {
            $counter++;
          }
        }
        return $filename;
    }





























}
