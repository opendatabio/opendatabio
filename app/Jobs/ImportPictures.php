Individual<?php

namespace App\Jobs;

use App\Models\Picture;
use App\Models\Language;
use App\Models\UserTranslation;
use App\Models\Location;
use App\Models\Voucher;
use App\Models\Taxon;
use App\Models\Individual;
use App\Models\ODBFunctions;
use App\Models\Tag;
use Spatie\SimpleExcel\SimpleExcelReader;
use Lang;
use Filepond;
//use Illuminate\Support\Facades\Storage;

class ImportPictures extends ImportCollectable
{
  /**
   * Execute the job.
   */
  public function inner_handle()
  {


      //get data sent from controller
      $data = $this->userjob->data['data'];

      //read attribute table file path
      $pathToFile = $data['pictures_attribute_table'];
      $rows = SimpleExcelReader::create($pathToFile)->getRows()->toArray();
      $message = serialize($rows);

      //$

      //validate table headers
      if (!$this->hasRequiredKeys(['object_id', 'object_type','filename','collector'],$rows[0])) {
          $this->appendLog('ERROS: '.Lang::get('messages.invalid_or_missing_columns'));
          return false;
      }

      //for each picture, get attribute data and store if all valid
      $pictures_paths = $data['pictures_paths'];

      if (!$this->setProgressMax($pictures_paths)) {
        return;
      }

      //call filepond to get image paths
      $filepond = app(Filepond::class);

      foreach($pictures_paths as $path) {
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
            $this->skipEntry($dt,'Attribute data invalid');
          } else {
            $this->import($dt,$filepath);
          }
        } else {
          $this->skipEntry($filename,' image filename not found in attribute table');
        }
      }
  }

  protected function validateData(&$data)
  {
      if(!$this->validateObject($data)) {
            return false;
      }
      if(!$this->validateDescriptions($data)) {
            return false;
      }
      if(!$this->validateTags($data)) {
            return false;
      }
      if(!$this->validateCollector($data)) {
            return false;
      }
      return true;
  }

  protected function validateCollector(&$data) {
      if (!isset($this->header)) {
        $this->header = array();
      }
      $collectors = $this->extractCollectors($data['collector'], $data, 'collector');
      if (null == $collectors)  {
        $this->appendLog('Missing image collector');
        return false;
      }
      $data['collector'] = $collectors;
      return true;
  }



  protected function validateTags(&$data) {
    if (!isset($data['tags'])) {
      return true;
    }
    $tags = $data['tags'];
    if (!is_array($tags)) {
        $tags = explode(";",$tags);
    }
    $newtags = array();
    foreach($tags  as $tag) {
        $odbtag = ODBFunctions::validRegistry(Tag::select('id'), $tag, ['id']);
        if (!$odbtag) {
          break;
        } else {
          $newtags[] = $odbtag->id;
        }
    }
    if (!count($tags)==count($newtags)) {
        $this->appendLog('Some tag ids are invalid');
        return  false;
    }
    $data['tags'] = $newtags;
    return true;
  }

  protected function validateDescriptions(&$data)
  {
      //which keys have $descriptions
      $keys = array();
      foreach($data as $key => $value) {
          if (preg_match("/description/i",$key)){
             $keys[] = $key;
          }
      }
      //descriptions are not mandatory if empty
      if (count($keys)==0) {
        return true;
      }
      $descriptions = array();
      foreach($keys  as $key) {
          //get language code or id in pattern description_lang
          $desc_lang = explode("_",$key)[1];
          $valid = ODBFunctions::validRegistry(Language::select('id'), $desc_lang, ['id', 'code', 'name']);
          if (!$valid) {
            break;
          } else {
            $descriptions[$valid->id] = $data[$key];
          }
      }
      if (!count($keys)==count($descriptions)) {
          $this->appendLog('Some language keys for description translation where not found');
          return  false;
      }
      $data['description'] = $descriptions;
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
          $this->appendLog('WARNING: Object '.$object_type.' - '.$data['object_id'].' not found');
          return false;
      }
  }

  protected function  import($line,$filepath)
  {

    //create with object
    $object = array('object_type' => $line['object_type'],'object_id' => $line['object_id']);
    $picture = Picture::create($object);

    //save image
    //$contents = file_get_contents($filepath);
    $metadata = null;
    try {
        $img = Image::make($filepath);
        $metadata = $img->exif();
        $picture->saveImage($img);
        //$picture->saveImage($contents);
    } catch (\Intervention\Image\Exception\NotReadableException $e) {
        $picture->delete();
        $this->skipEntry($line,$e->getMessage().' at '.$e->getFile().'+'.$e->getLine().' on picture  ['.$line['object_type'].' '.$line['object_id'].'] '.$e->getTraceAsString());
        return false;
    }

    if (null != $metadata) {
      $picture->metadata = json_encode($metadata);
      $picture->save();
    }


    // syncs tags
    if (isset($line['tags'])) {
      $picture->tags()->sync($line['tags']);
    }

    // syncs collectors
    $collectors = $line['collector'];
    foreach ($collectors as $collector) {
        $picture->collectors()->create(['person_id' => $collector]);
    }
    // syncs descriptions
    $descriptions = $line['description'];
    foreach ($descriptions as $key => $translation) {
        $picture->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
    }
    $this->affectedId($picture->id);

    return true;
  }



}
