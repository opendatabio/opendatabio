<?php
/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Jobs;

use App\Dataset;
use App\Project;
use App\Measurement;
use App\Plant;
use App\Voucher;
use App\Taxon;
use App\Location;
use App\ODBFunctions;
use App\ODBTrait;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use DB;
use Auth;
use Lang;
use Mail;
use Activity;
use ZipArchive;
use App\Http\Api\v0\Controller;
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

        //Get a LazyCollection for low memory use
        $measurements = Measurement::select('*','measurements.date as valueDate')->where('dataset_id',$data['id'])->cursor();


        /* Fields and assessors to export as the Measurement table */
        $fields = ['id', 'measured_type', 'measured_id','traitName', 'valueActual','valueDate','traitUnit','datasetName','measuredFullname', 'measuredTaxonName','measuredTaxonFamily','measuredProject'];

        /* PREP AND SAVE MEASUREMENTS TO FILE IN STORAGE  */
        $basename = 'dataset_'.Auth::user()->id.'_'.$data['id'];
        $filename = $basename."_measurements.csv";
        $files = array($filename);
        $path = 'downloads_temp/'.$filename;
        $writer = SimpleExcelWriter::create(public_path($path));

        //$this->appendLog("RAW DATA:".$path." datA".serialize($measurements));


        /* Define job progress
          * 50% for the MEASUREMENTS
          * 30% for Measured objects attributes
          * 10% for Locations attributes (when Measured is not a Location)
          * 10% for Traits and Dataset METADATA
        */
        $n_measurements = $measurements->count();
        $nrecs = $n_measurements*2; //50 % will be computed for measurements
        $this->userjob->setProgressMax($nrecs);

        /* Empty arrays for associated models exports */
        $measured_plants = array();
        $measured_vouchers = array();
        $measured_taxons = array();
        $measured_locations = array();
        $measured_traits = array();
        $taxons = array();
        $locations = array();


        /* Store measurements in file */
        foreach ($measurements as $measurement) {

            /* Store related ids for separed export */
            $locations[] = $measurement->location_id;
            $measured_traits[] = $measurement->trait_id;
            $taxons[] = $measurement->taxon_id;
            if ($measurement->measured_type == 'App\Plant') {
              $measured_plants[] = $measurement->measured_id;
            }
            if ($measurement->measured_type == 'App\Voucher') {
              $measured_vouchers[] = $measurement->measured_id;
            }
            if ($measurement->measured_type == 'App\Taxon') {
              $measured_taxons[] = $measurement->measured_id;
            }
            if ($measurement->measured_type == 'App\Location') {
              $measured_locations[] = $measurement->measured_id;
            }


            /*$aslocs = array_unique($measurement->values()->pluck('location_id')->toArray());
            $ids = implode(',',$measurement->values()->pluck('id')->toArray());
            $url = 'api/measurements?id='.$ids.'&fields=simple';
            $pegadata = Request::create($url,'GET');
            $response = app()->handle($pegadata);
            $rr = json_decode($response->content(),true);
            $writer->addRows($rr['data']);
            */

            $writer->addRow($measurement->only($fields));

            $this->userjob->tickProgress();
        }

        /* Unique related values */
        $measured_plants = array_unique($measured_plants);
        $measured_vouchers = array_unique($measured_vouchers);
        $measured_taxons = array_unique($measured_taxons);
        $measured_locations = array_unique($measured_locations);
        $measured_traits = array_unique($measured_traits);
        $locations = array_unique($locations);
        $taxons = array_unique($taxons);
        $measured_taxons = array_unique(array_merge($measured_taxons,$taxons));
        $measured_locations = array_unique(array_merge($measured_locations,$locations));

        /* Define progress for rest */
        $totals = count($measured_plants)+count($measured_vouchers)+count($measured_taxons)+count($measured_locations);
        $step = ceil($totals/50);
        $progress_echo = range(1,$totals,$step);
        $progress_idx = 1;
        //$progress = round(100 * $this->userjob->progress / $this->userjob->progress_max);


        /* Measured Plants */
        if (count($measured_plants)>0) {
          //get these locations with stringfied geometries
          $plants_chunk = array_chunk($measured_plants,10000);

          //save locations to file
          $filename = $basename."_measuredPlants.csv";
          $files[] = $filename;
          $path = 'downloads_temp/'.$filename;
          $lwriter = SimpleExcelWriter::create(public_path($path));



          $plant_fields = ['id','fullName', 'taxonName', 'taxonFamily','location_id', 'locationName', 'locationParentName','tag', 'date', 'notes', 'relativePosition','xInParentLocation','yInParentLocation','projectName'];

          foreach($plants_chunk as $chunk) {
            $plants = Plant::select('*', DB::raw('AsText(plants.relative_position) as relativePosition'))->whereIn('id',$chunk)->cursor();

            foreach ($plants as $plant) {
              if (in_array($progress_idx,$progress_echo)) {
                $this->userjob->tickProgress();
              }
              $lwriter->addRow($plant->only($plant_fields));
              $progress_idx = $progress_idx++;
            }
          }

        }

        /* Measured vouchers */
        if (count($measured_vouchers)>0) {

          //get these locations with stringfied geometries
          $vouchers_chunk = array_chunk($measured_vouchers,10000);

          $filename = $basename."_measuredVouchers.csv";
          $files[] = $filename;
          $path = 'downloads_temp/'.$filename;
          $lwriter = SimpleExcelWriter::create(public_path($path));
          $voucher_fields = ['fullname', 'taxonName', 'id', 'parent_type', 'parent_id', 'date', 'notes', 'project_id'];

          foreach($vouchers_chunk as $chunk) {
            $vouchers = Voucher::select('*')->whereIn('id',$chunk)->cursor();
            foreach ($vouchers as $voucher) {
              if (in_array($progress_idx,$progress_echo)) {
                $this->userjob->tickProgress();
              }
              $lwriter->addRow($voucher->only($voucher_fields));
              $progress_idx = $progress_idx++;
            }
          }
        }


        /* Measured Taxons */
        if (count($measured_taxons)>0) {
          $filename = $basename."_measuredTaxons.csv";
          $files[] = $filename;
          $path = 'downloads_temp/'.$filename;
          $lwriter = SimpleExcelWriter::create(public_path($path));



          //save
          $taxons = Taxon::select('*',DB::raw('odb_txname(name, level, parent_id) as fullname'))->whereIn('id',$measured_taxons)->cursor();

          $taxon_fields = ['id','fullname', 'levelName', 'authorSimple', 'bibreferenceSimple', 'valid', 'senior_id', 'parent_id','author_id','family','notes'];
          foreach ($taxons as $taxon) {
            if (in_array($progress_idx,$progress_echo)) {
              $this->userjob->tickProgress();
            }
            $lwriter->addRow($taxon->only($taxon_fields));
            $progress_idx = $progress_idx++;
          }
        }


        /* Save LOCATIONS for measured objects if any
          * include locations and parent locations in location table
        */
        if (count($measured_locations)>0) {


          //include imediate parents for each distinct location
          $locations_parents = array_unique(Location::whereIn('id',$measured_locations)->cursor()->pluck('parent_id')->toArray());
          $measured_locations = array_merge($measured_locations,$locations_parents);

          //get these locations with stringfied geometries
          $locations = Location::select('*')->whereIn('id',$locations)->withGeom()->orderBy('adm_level')->cursor();

          //save locations to file
          $filename = 'dataset_'.Auth::user()->id.'_'.$data['id']."_measuredLocations.csv";
          $files[] = $filename;
          $path = 'downloads_temp/'.$filename;
          $lwriter = SimpleExcelWriter::create(public_path($path));


          $progress = round(100 * $this->userjob->progress / $this->userjob->progress_max);

          $loc_fields = ['id', 'name', 'levelName', 'parentName','parent_id','x','y','startx','starty','centroid_raw','area','geom', 'distance','full_name'];
          foreach ($locations as $location) {
            if (in_array($progress_idx,$progress_echo)) {
              $this->userjob->tickProgress();
            }
            $lwriter->addRow($location->only($loc_fields));
            $progress_idx = $progress_idx++;
          }
        }


        /* PREP TRAIT DEFINITIONS AS METADATA TABLE */
        if (count($measured_traits)>0) {
          //get these locations with stringfied geometries
          $odbtraits = ODBTrait::select('*',DB::raw('odb_traittypename(type) as trait_type'))->whereIn('id',$measured_traits)->cursor();

          //save locations to file
          $filename = $basename."_measuredTraits.csv";
          $files[] = $filename;
          $path = 'downloads_temp/'.$filename;
          $lwriter = SimpleExcelWriter::create(public_path($path));


          $progress = round(100 * $this->userjob->progress / $this->userjob->progress_max);
          $odbtrait_fields = ['id', 'trait_type','export_name','unit','link_type','name','description'];
          foreach ($odbtraits as $odbtrait) {
            if (in_array($progress_idx,$progress_echo)) {
              $this->userjob->tickProgress();
            }
            $lwriter->addRow($odbtrait->only($odbtrait_fields));
            $progress_idx = $progress_idx++;
          }
        }


        /* ADD README TO FILE PACK WITH DATASET DETAILS AND POLICIES */
        $dataset = Dataset::find($data['id']);
        $readme = "\n==========README=========\n";
        $readme .= Lang::get('messages.dataset').": ".$dataset->name."\n";
        $readme .= Lang::get('messages.downloaded')." - ".now()." - ";
        $readme .= env('APP_URL')."\n";
        $readme .= "\n================\n";
        $readme .= Lang::get('messages.admins').":\n";
        foreach($dataset->users()->wherePivot('access_level', '=',Project::ADMIN)->get() as $admin) {
          if ($admin->person->fullname) {
            $adm = $admin->person->fullname." - ".$admin->email;
          } else {
            $adm = $admin->email;
          }
          $readme .= "\t".$adm."\n";
        }
        $readme .= "\n\n================\n";
        if ($dataset->notes) {
          $readme .=  Lang::get('messages.notes').": ".$dataset->notes;
          $readme .= "\n\n================\n";
        }

        if ($dataset->policy) {
          $readme .=  Lang::get('messages.dataset_policy').": ".$dataset->policy;
          $readme .= "\n\n================\n";
        }
        if($dataset->references->where('mandatory',1)->count()) {
          $readme .=  Lang::get('messages.dataset_bibreferences_mandatory').": \n";
          foreach($dataset->references->where('mandatory',1) as $reference)
          {
            $readme .=  $reference->first_author." ".$reference->year.". ".$reference->title."\n\n";
            $readme .=  $reference->bibtex."\n\n";
          }
          $readme .= "\n\n================\n";
        }
        $readme .=  Lang::get('messages.files').": \n";
        foreach($files as $file) {
          $readme .=  $file." \n";
        }
        $readme .= "\n\n================\n";
        $readme .= Lang::get('messages.dataset_request_distribution_agreement');
        $readme .= "\n\n================\n";
        $filename = "README_".$basename.".txt";
        $files[] = $filename;
        $path = 'downloads_temp/'.$filename;
        $fn = fopen(public_path($path),'w');
        fwrite($fn,$readme);
        fclose($fn);


        //ZIP THE FILES INTO A SINGLE BUNDLE
        // Define Dir Folder
        $public_dir=public_path('downloads_temp');

        $today = now();
        $datasetname = $dataset->name;

        // Zip File Name
        $zipFileName = Str::ascii($datasetname);
        $zipFileName = str_replace("  "," ",$zipFileName);
        $zipFileName = str_replace(" ","_",$zipFileName);
        $zipFileName = $zipFileName."_".$today->toDateString().".zip";

        // Create ZipArchive Obj
        $zip = new ZipArchive;
        $zipfile =  $public_dir . '/' . $zipFileName;
        if (true === ($zip->open($zipfile, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
            // Add Files in ZipArchive
            foreach($files as $file) {
                $zip->addFile($public_dir."/".$file,$file);
            }
            $zip->close();

            //delete files
            foreach($files as $file) {
                unlink($public_dir."/".$file);
            }
        }



        //LOG THE FILE FOR USER DOWNLOAD
        $file = "Files for dataset <strong>".$datasetname."</strong> prepared ".$today." ";
        $tolog = $file."<br><a href='".url('downloads_temp/'.$zipFileName)."' download >".$zipFileName."</a><br>".Lang::get('messages.dataset_download_file_tip');
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
        $to_email = Auth::user()->email;
        if (isset(Auth::user()->person->full_name)) {
          $to_name = Auth::user()->person->full_name;
        } else {
          $to_name = $to_email;
        }
        $data = array(
          'to_name' => $to_name,
          'content' => Lang::get('messages.dataset_downloaded_message').":<br><br>".Lang::get('messages.dataset').":  &nbsp;<strong>".$datasetname."</strong> [".$today.",  @ Job Id# ".$this->userjob->id."].<br>URL:&nbsp;<a href='".env('APP_URL')."'>".env('APP_URL')."</a><br><br>".Lang::get('messages.dataset_download_file_tip')
        );
        Mail::send('common.email', $data, function($message) use ($to_name, $to_email) {
          $message->to($to_email, $to_name)->subject(Lang::get('messages.dataset_request').' - '.env('APP_NAME'));
        });



      }
}