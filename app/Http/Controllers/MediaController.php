<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Taxon;
use App\Models\Person;
use App\Models\UserTranslation;
use Lang;
use App\Models\Language;
use App\Models\Media;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Location;
use App\Models\Voucher;
use App\Models\Individual;
use App\Models\UserJob;
use App\Jobs\ImportMedia;
use Activity;
use App\Models\ActivityFunctions;
use App\DataTables\ActivityDataTable;
use Log;
use Auth;
use Filepond;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;


class MediaController extends Controller
{

  public function indexTaxons($id)
  {
      $model = Taxon::findOrFail($id);
      //get all the media for taxon and its descendants
      $media = $model->mediaDescendantsAndSelf();
      $media = $media->paginate(20);
      return view('media.index', compact('model','media'));
  }

  public function indexLocations($id)
  {
      $model = Location::findOrFail($id);
      //get all the media for taxon and its descendants
      $media = $model->mediaDescendantsAndSelf();
      $media = $media->paginate(20);
      return view('media.index', compact('model','media'));
  }

  public function indexIndividuals($id)
  {
      $model = Individual::findOrFail($id);
      $media = $model->media();
      $media = $media->paginate(20);
      return view('media.index', compact('model','media'));
  }

  public function indexVouchers($id)
  {
      $model = Voucher::findOrFail($id);
      $media = $model->media();
      if ($media->count()) {
        $media = $media->paginate(20);
      } else {
        $media = null;
      }
      return view('media.index', compact('model','media'));
  }


  public function show($id)
  {
    $media = Media::findOrFail($id);
    return view('media.show', compact('media'));
  }



  public function createTaxons($id)
  {
      $object = Taxon::findOrFail($id);
      return $this->createMedia($object);
  }

  public function createLocations($id)
  {
      $object = Location::findOrFail($id);
      return $this->createMedia($object);
  }

  public function createVouchers($id)
  {
      $object = Voucher::findOrFail($id);
      return $this->createMedia($object);
  }

  public function createIndividuals($id)
  {
      $object = Individual::findOrFail($id);
      return $this->createMedia($object);
  }

  /* vouchers images are linked to the individual
  * voucher_id is added as custom_properties
  */
  public function createMedia($object)
  {
      $languages = Language::all();
      $tags = Tag::all();
      $persons = Person::all();
      $projects = Project::all();
      $customProperties = null;
      if (class_basename($object) == 'Voucher') {
        $customProperties = json_encode(['voucher_id' => $object->id]);
        $object = $object->individual;
      }
      $validMimeTypes = 'image:apng,gif,jpeg,png,tif,svg; video: mp4,ogv,webm; audio: mp3,oga,wav';
      return view('media.create', compact('object', 'languages', 'tags','persons', 'projects','validMimeTypes','customProperties'));
  }

  public function edit($id)
  {
    $media = Media::findOrFail($id);
    $object = $media->model()->first();
    $languages = Language::all();
    $tags = Tag::all();
    $persons = Person::all();
    $projects = Project::all();
    $validMimeTypes = 'image:apng,gif,jpeg,png,tif,svg; video: mp4,ogv,webm; audio: mp3,oga,wav';
    return view('media.create', compact('languages', 'tags','persons', 'projects','validMimeTypes','media','object'));
  }

  public function store(Request $request)
  {
    // editing or saving
    $this->authorize('create', Media::class);
    $licenses = implode(',',config('app.creativecommons_licenses'));

    $mimes = 'mimes:apng,gif,jpeg,png,svg,tif,mp3,oga,wav,mp4,ogv,webm';
    $this->validate($request, [
          'uploaded_media' => 'file|required|'.$mimes,
          'description' => 'array',
          'tags' => 'array',
          'collector' => 'required_unless:license,CC0|array|min:1',
          'license' => 'required|string|max:191|in:'.$licenses,
          'date' => 'date_format:Y-m-d|nullable',
          'project_id' => 'integer|nullable',
    ]);

    /* add media to model */
    $licenseVersion = (null != $request->license_version) ? (string) $request->license_version : config('app.creativecommons_version')[0];
    $license = $request->license." ".$licenseVersion;

    /* model to add media to */
    $model = app($request->model_type)->findOrFail($request->model_id);

    /* new file name for media */
    $mediaExtension = mb_strtolower($request->file('uploaded_media')->getClientOriginalExtension());
    $originalFilename = $request->file('uploaded_media')->getClientOriginalName();
    $newMediaName = uniqid();
    $newFilename = $newMediaName.".".$mediaExtension;

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

    /* check if there is already a media with identifica name  for this model */
    $searchStr = 'originalFilename":"'.$originalFilename.'"';
    $hasSimilarMedia  = $model->media()
    ->where('custom_properties','like','%'.$searchStr.'%');
    if ($hasSimilarMedia->count()>0) {
      $message = 'Media with name <strong>'.$originalFilename.'</strong> is already registered for this '.class_basename($model).'. If really different, change name to import';
      return redirect()->back()->withStatus($message);
    }


    //custom properties
    $customProperties =
    [
      'originalFilename' => $originalFilename,
      'license' => $license,
      'date' => (null != $request->date) ? $request->date : today(),
      'user_id' => Auth::user()->id,
      'notes' => (null != $request->notes) ? $request->notes : null,
    ];
    /* will add model specific properties if present */
    if ($request->custom_properties) {
      $customProperties = array_merge($customProperties,(array) json_decode($request->custom_properties));
    }

    /* add media to odb */
    $model->addMedia($request->file('uploaded_media')->getRealPath())
    ->withCustomProperties($customProperties)
    ->usingFileName($newFilename)
    ->usingName($newMediaName)
    ->toMediaCollection($collectionName);

    /* save media collectors */
    $media = $model->media()->where('name',$newMediaName)->first();
    if ($request->tags) {
      $media->tags()->sync($request->tags);
    }
    /* syncs collectors if informed */
    if ($request->collector) {
      foreach ($request->collector as $collector) {
          $media->collectors()->create(['person_id' => $collector]);
      }
    }

    if (null != $request->project_id) {
      $media->project_id = $request->project_id;
      $media->save();
    }
    /* syncs title translation */
    /* will be placed as description */
    foreach ($request->description as $key => $translation) {
        $media->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
    }

    $message = "Media was saved for ".class_basename($model)." ".$model->fullname;

    return redirect()->back()->withStatus($message);
  }


  /* one can only update associated data */
  public function update(Request $request, $id)
  {

    $media = Media::findOrFail($id);
    $this->authorize('update', $media);
    // editing or saving
    $licenses = implode(',',config('app.creativecommons_licenses'));
    $this->validate($request, [
          'description' => 'array',
          'tags' => 'array',
          'collector' => 'required_unless:license,CC0|array|min:1',
          'license' => 'required|string|max:191|in:'.$licenses,
          'date' => 'date_format:Y-m-d|nullable',
          'project_id' => 'integer|nullable',
    ]);


    $oldCustomProperties = [];
    $newCustomProperties = [];
    /* add media to model */
    $licenseVersion = (null != $request->license_version) ? (string) $request->license_version : config('app.creativecommons_version')[0];
    $license = $request->license." ".$licenseVersion;

    if ($license != $media->license) {
      $oldCustomProperties['license'] = $media->license;
      $media->setCustomProperty('license',$license);
      $newCustomProperties['license'] = $license;
    }

    $date = (null != $request->date) ? $request->date : today()->format('Y');
    if ($date != $media->getCustomProperty('date')) {
      $oldCustomProperties['date'] = $media->date;
      $media->setCustomProperty('date',$date);
      $newCustomProperties['date'] = $date;
    }

    $notes = (null != $request->notes) ? $request->notes : null;
    if ($notes != $media->getCustomProperty('notes')) {
      $media->setCustomProperty('notes',$notes);
    }

    if ($request->project_id != $media->project_id) {
      $oldCustomProperties['project_id'] = $media->project_id;
      $media->project_id = $request->project_id;
      $newCustomProperties['project_id'] = $request->project_id;
    }
    $media->save();

    if ($request->tags) {
      $media->tags()->sync($request->tags);
    }
    /* syncs collectors if informed */
    if ($request->collector) {
      //did collectors changed?
      // "sync" collectors. See app/Project.php / setusers()
      $current = $media->collectors->pluck('person_id');
      $detach = $current->diff($request->collector)->all();
      $attach = collect($request->collector)->diff($current)->all();
      if (count($detach) or count($attach)) {
          //delete old collectors
          $media->collectors()->delete();
          //save collectors and identify main collector
          foreach ($request->collector as $collector) {
            $media->collectors()->create(['person_id' => $collector]);
          }
      }
      //log changes in collectors if any
      ActivityFunctions::logCustomPivotChanges($media,$current->all(),$request->collector,'media','collector updated',$pivotkey='person');
    }

    /* syncs title translation */
    /* will be placed as description */
    $old_translations = array('translation' => $media->translations->flatMap(function($translation) {
      $newkey = $translation->language_id."_".$translation->translation_type;
      return array($newkey => $translation->translation);
    })->toArray());
    foreach ($request->description as $key => $translation) {
        $media->setTranslation(UserTranslation::DESCRIPTION, $key, $translation);
    }

    /* log translations changes if any */
    $new_translations = array('translation' => $media->translations->flatMap(function($translation) {
      $newkey = $translation->language_id."_".$translation->translation_type;
      return array($newkey => $translation->translation);
    })->toArray());
    $logName = 'media';
    $logDescription = 'title updated';
    ActivityFunctions::logTranslationsChanges($media,$old_translations,$new_translations,$logName,$logDescription,"");

    return redirect('media/'.$id)->withStatus(Lang::get('messages.saved'));
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param int $id
   *
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
      $media = Media::findOrFail($id);
      $this->authorize('delete', $media);
      $mediaId = $media->id;
      $modelRoute = $media->model()->first()->getTableName();
      $modelId = $media->model->id;
      try {
          /* this will remove model and media files */
          $media->delete();
      } catch (\Illuminate\Database\QueryException $e) {
          return redirect()->back()
              ->withErrors([Lang::get('messages.fk_error')]);
      }
      $hasActivity = Activity::where('subject_id',$mediaId)->where('subject_type',Media::class);
      if ($hasActivity->count()) {
        try {
          /* this will remove model and media files */
          $hasActivity->delete();
        } catch (\Illuminate\Database\QueryException $e) {
          return redirect($modelRoute."/".$modelId)
            ->withErrors([Lang::get('messages.media_activity_deletion_failed')]);
        }
      }

      return redirect($modelRoute."/".$modelId)->withStatus(Lang::get('messages.removed'));
  }

  public function activity($id, ActivityDataTable $dataTable)
  {
      $object = Media::findOrFail($id);
      return $dataTable->with('activity', $id)->render('common.activity',compact('object'));
  }


  /* BATCH UPLOAD FUNCTION */
  public function uploadForm(){

    return view('media.upload');

  }


  /*
    Controll for batch upload of images

  */
  public function uploadSubmit(Request $request){

    $this->authorize('create', Media::class);
    $this->authorize('create', UserJob::class);
    /* file is the list of filepond ServerIDs values that allows to retrieve the filepond uploaded images */
    $media = $request->input('file');
    if (!$request->hasFile('media_attribute_table') || count($media)==0) {
        $message = Lang::get('messages.media_attributes_file_missing');
        $filepond = app(Filepond::class);
        foreach($media as $path) {
          $filepath = $filepond->getPathFromServerId($path);
          $info = pathinfo($filepath);
          unlink($filepath);
          rmdir($info['dirname']);
        }
    } else {
      /*
          Validate attribute file
          Validate file extension and maintain original if valid or else
          Store may save a csv as a txt, and then the Reader will fail
      */
      $valid_ext = array("CSV","csv","ODS","ods","XLSX",'xlsx');
      $ext = $request->file('media_attribute_table')->getClientOriginalExtension();
      if (!in_array($ext,$valid_ext)) {
        $message = Lang::get('messages.invalid_file_extension');
        //unlink all media files
        //call filepond to get image paths
        $filepond = app(Filepond::class);
        foreach($media as $path) {
          $filepath = $filepond->getPathFromServerId($path);
          $info = pathinfo($filepath);
          unlink($filepath);
          rmdir($info['dirname']);
        }
      } else {
        /* store file for later use and get path */
        $newname =  uniqid().".".$ext;
        $path = $request->file('media_attribute_table')->storeAs('temp', $newname);
        $path = Storage::path($path);
        /* send request as job */
        UserJob::dispatch(ImportMedia::class, ['data' => ['media_paths' => $media, 'media_attribute_table' => $path]]);
        $message = Lang::get('messages.dispatched');
      }
    }
    return redirect('media/import-form')->withStatus($message);
  }



}
