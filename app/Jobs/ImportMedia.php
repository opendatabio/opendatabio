<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\Language;
use App\Models\UserTranslation;
use App\Models\Location;
use App\Models\Voucher;
use App\Models\Taxon;
use App\Models\Individual;
use App\Models\ODBFunctions;
use App\Models\Tag;
use App\Models\Project;
use Spatie\SimpleExcel\SimpleExcelReader;
use Lang;
use Filepond;
use Auth;
//use Illuminate\Support\Facades\Storage;

class ImportMedia extends ImportCollectable
{
  /**
   * Execute the job.
   */
  public function inner_handle()
  {


      //get data sent from controller
      $data = $this->userjob->data['data'];

      //read attribute table file path
      $pathToFile = $data['media_attribute_table'];
      $rows = SimpleExcelReader::create($pathToFile)->getRows()->toArray();
      $message = serialize($rows);

      //$

      //validate table headers
      if (!$this->hasRequiredKeys(['object_id', 'object_type','filename'],$rows[0])) {
          $this->appendLog('ERROS: '.Lang::get('messages.invalid_or_missing_columns'));
          return false;
      }

      //for each media, get attribute data and store if all valid
      $media_paths = $data['media_paths'];

      if (!$this->setProgressMax($media_paths)) {
        return;
      }

      //call filepond to get image paths
      $filepond = app(Filepond::class);

      foreach($media_paths as $path) {
        if ($this->isCancelled()) {
          break;
        }
        $this->userjob->tickProgress();

        //get image name and search for attributes
        $filepath = $filepond->getPathFromServerId($path);
        $filename = explode("/",$filepath);
        $filename = $filename[(count($filename)-1)];
        $key = array_search($filename, array_column($rows, 'filename'));
        if ($key !== False) {
          //read attributes of image and import if valid
          $dt = $rows[$key];
          if (!$this->validateData($dt)) {
            $info = pathinfo($filepath);
            unlink($filepath);
            rmdir($info['dirname']);
            //$this->skipEntry($dt,'Attribute data invalid');
          } else {
            $this->import($dt,$filepath);
          }
        } else {
          $info = pathinfo($filepath);
          unlink($filepath);
          rmdir($info['dirname']);
          $this->skipEntry($filename,'media name not found in attribute table');
        }
      }
  }

  protected function validateData(&$data)
  {
      if(!$this->validateObject($data)) {
            return false;
      }
      if(!$this->validateTitle($data)) {
            return false;
      }
      if(!$this->validateTags($data)) {
            return false;
      }
      if(!$this->validateCollector($data)) {
            return false;
      }
      if(!$this->projectValidate($data)) {
            return false;
      }
      if(!$this->validateLicense($data)) {
            return false;
      }
      if(!$this->validateDate($data)) {
        $this->skipEntry($data, 'date'.' '.$data['date'].' is not valid');
        return false;
      }


      return true;
  }

public function validateDate(&$data)
{
  if (!isset($data['date']) or null == $data['date']) {
    $data['date'] = today();
    return true;
  }
  $date = $data['date'];
  if (strpos($date, '/') !== false) {
      $date = explode('/', $date);
  } else {
      if (strpos($date, '-') !== false) {
        $date = explode('-', $date);
      }
  }
  if (count($date) != 3) {
    return false;
  }
  return checkdate($date[1], $date[2], $date[0]);
}

    public function projectValidate(&$data)
    {
      $project = isset($data['project']) ? $data['project'] : (isset($data['project_id']) ? $data['project_id'] : null);
      if (null == $project) {
          /* not informed is valid */
          return true;
      }
      $valid = ODBFunctions::validRegistry(Project::select('id'),$project,['id','name']);
      if (null === $valid) {
          $this->skipEntry($data, 'project'.' '.$project.' was not found in the database');
            return false;
      }
      $data['project'] = $valid->id;
      return true;
    }



  protected function validateCollector(&$data) {
      if (!isset($this->header)) {
        $this->header = [];
      }
      $collectors = $this->extractCollectors($data['collector'], $data, 'collector');
      if (null == $collectors and null != $data['collector'])  {
        $this->appendLog('Error in media collector');
        return false;
      }
      $data['collector'] = $collectors;
      return true;
  }



  protected function validateTags(&$data) {
    if (!isset($data['tags']) or $data['tags']==null) {
      return true;
    }
    $tags = $data['tags'];
    if (!is_array($tags)) {
      if (strpos($tags, '|') !== false) {
          $tags = explode('|', $tags);
      } else {
          if (strpos($tags, ';') !== false) {
            $tags = explode(';', $tags);
          } else {
            if (strpos($tags, ',') !== false) {
              $tags = explode(',', $tags);
            } else {
              $tags = [$tags];
            }
          }
      }
    }
    $newtags = [];
    foreach($tags  as $tag) {
        $isTagNumeric = (int) $tag;
        if ($isTagNumeric > 0) {
          $odbtag = Tag::where('id',$tag);
        } else {
          $odbtag =  Tag::whereHas('translations',function($translation) use($tag) {
              $translation->where('translation','like',$tag);});
        }
        if ($odbtag->count()==1) {
          $newtags[] = $odbtag->first()->id;
        } else {
          break;
        }
    }
    if (!count($tags)==count($newtags)) {
        $this->appendLog('Some tag ids are invalid');
        return  false;
    }
    $data['tags'] = $newtags;
    return true;
  }

  protected function validateTitle(&$data)
  {
      //which keys have $descriptions
      $keys = [];
      foreach($data as $key => $value) {
          if (preg_match("/title/i",$key)){
             $keys[] = $key;
          }
      }
      //title are not mandatory if empty
      if (count($keys)==0) {
        return true;
      }
      $title = [];
      foreach($keys  as $key) {
          //get language code or id in pattern description_lang
          $desc_lang = explode("_",$key)[1];
          $valid = ODBFunctions::validRegistry(Language::select('id'), $desc_lang, ['id', 'code', 'name']);
          if (!$valid) {
            break;
          } else {
            $title[$valid->id] = $data[$key];
          }
      }
      if (!count($keys)==count($title)) {
          $this->appendLog('Some language keys for title translation where not found');
          return  false;
      }
      $data['title'] = $title;
      return true;
  }

  protected function validateObject(&$data)
  {
      $object_type = $data['object_type'];
      if (!in_array($data['object_type'],["Individual","Voucher","Location","Taxon"])) {
          $this->appendLog('object_type '.$object_type.' not found in ['.implode(";",["Individual","Voucher","Location","Taxon"]).']');
          return false;
      }
      if ('Location' === $object_type) {
          $query = Location::select('id')->where('id', $data['object_id'])->get();
          $data['object_type'] = Location::class;
      } elseif ('Taxon' === $object_type) {
          $query = Taxon::select('id')->where('id', $data['object_id'])->get();
          $data['object_type'] = Taxon::class;
      } elseif ('Individual' === $object_type) {
          $query = Individual::select('individuals.id')->where('id', $data['object_id'])->get();
          $data['object_type'] = Individual::class;
      } elseif ('Voucher' === $object_type) {
          $query = Voucher::select('id')->where('id', $data['object_id'])->get();
          $data['object_type'] = Voucher::class;
      }
      if (count($query)) {
          return true;
      } else {
          $this->appendLog('ERROR: Object '.$object_type.' - '.$data['object_id'].' not found');
          return false;
      }
  }

  public function validateLicense(&$data)
  {
    if (!isset($data['license']) or null == $data['license']) {
      $data['license'] = config('app.creativecommons_licenses')[0]." ".config('app.creativecommons_version')[0];
      return true;
    }
    $license = mb_strtoupper($data['license']);
    if (!in_array($license,config('app.creativecommons_licenses'))) {
      $this->skipEntry($data,'License '.$license.' is invalid');
      return false;
    }
    $data['license'] = mb_strtoupper($data['license'])." ".config('app.creativecommons_version')[0];
    return true;
  }


  protected function  import($line,$filepath)
  {



    /* new file name for media */
    $info = pathinfo($filepath);
    $mediaExtension = mb_strtolower($info['extension']);
    $originalFilename = $info['basename'];
    $newMediaName = uniqid();
    $newFilename = $newMediaName.".".$mediaExtension;


    // get the model
    $model = app($line['object_type'])::findOrFail($line['object_id']);
    if (!$model->count()) {
      $this->skipEntry($line,'Informed object is invvalid');
      /* remove file and file dir */
      unlink($filepath);
      rmdir($info['dirname']);
      return;
    }

    /* check if there is already a media with identifica name  for this model */
    $searchStr = 'originalFilename":"'.$originalFilename.'"';
    $hasSimilarMedia  = $model->media()
    ->where('custom_properties','like','%'.$searchStr.'%');
    if ($hasSimilarMedia->count()>0) {
      unlink($filepath);
      rmdir($info['dirname']);
      $this->skipEntry($line,'Media with name '.$originalFilename.' is already registered for this '.class_basename($model).'. If really different, change name to import!');
      return ;
    }

    /* define collection */
    $imgMimes = explode(",","apng,gif,jpeg,jpg,png,svg,tif");
    $videoMimes = explode(',',"mp4,ogv,webm");
    $audioMimes = explode(',',"mp3,oga,wav");
    $collectionName = null;
    if (in_array($mediaExtension,$imgMimes)) {
      $collectionName = "images";
    } elseif (in_array($mediaExtension,$videoMimes)) {
      $collectionName = "videos";
    } elseif (in_array($mediaExtension,$audioMimes)) {
      $collectionName = "audios";
    }

    /* if did not find a valid collection then extension is not valid */
    // TODO:  should implement type validation in filepond directly
    // this may fail when mime type is valid
    if (null == $collectionName) {
      unlink($filepath);
      rmdir($info['dirname']);
      $this->skipEntry($line," File extension invalid!");
      return ;
    }


    //custom properties
    $customProperties =
    [
      'originalFilename' => $originalFilename,
      'license' => $line['license'],
      'date' => isset($line['date']) ? $line['date'] : today(),
      'user_id' => Auth::user()->id,
      'notes' => (null != $line['notes']) ? $line['notes'] : null,
    ];

    /*
      * if voucher, then add media to its individual
      * and voucher_id to media customProperties
    */
    if ($line['object_type'] == 'App\Models\Voucher') {
      $customProperties['voucher_id'] = (int) $line['object_id'];
      $model = $model->individual;
    }
    $model->addMedia($filepath)
    ->withCustomProperties($customProperties)
    ->usingFileName($newFilename)
    ->usingName($newMediaName)
    ->toMediaCollection($collectionName);

    /* save media collectors */
    $media = $model->media()->where('name',$newMediaName)->first();
    if ($line['tags']) {
      $media->tags()->sync($line['tags']);
    }
    /* syncs collectors if informed */
    if (null != $line['collector']) {
      foreach ($line['collector'] as $collector) {
          $media->collectors()->create(['person_id' => $collector]);
      }
    }

    if (null != $line['project']) {
      $media->project_id = $line['project'];
      $media->save();
    }
    /* syncs title translation */
    /* will be placed as description */
    foreach ($line['title'] as $key => $translation) {
        $media->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
    }

    /* remove temporary folder, file deleted by media library */
    rmdir($info['dirname']);


    $this->affectedId($media->id);

    return true;
  }



}
