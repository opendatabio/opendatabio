@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">

      <div class="panel-heading">
        @if (isset($media))
          @lang('messages.media_edit_for_model'):
        @else
          @lang('messages.media_new_for_model'):
        @endif
        &nbsp;
        <strong>{{ class_basename($object) }}</strong>
        {!! $object->rawLink() !!}
        &nbsp;
        <a data-toggle="collapse" href="#media_hint" class="btn btn-default">?</a>
        <div id="media_hint" class="panel-collapse collapse">
          <br>
          @lang('messages.media_mimes_hint')
          <code>
            {{ $validMimeTypes }}
          </code>
        </div>
      </div>
      <div class="panel-body">

        <!-- Display Validation Errors -->
  		   @include('common.errors')
        <div id="ajax-error" class="collapse alert alert-danger">
          @lang('messages.whoops')
        </div>

@if (isset($media))
		<form action="{{ url('media/'.$media->id) }}" method="POST" class="form-horizontal" enctype="multipart/form-data">
      {{ method_field('PUT') }}
@else
		 <form action="{{ url('media') }}" method="POST" class="form-horizontal" enctype="multipart/form-data">
@endif

  <!-- csrf protection -->
  {{ csrf_field() }}

  <input type="hidden"  name="model_id" value="{{$object->id}}">
  <input type="hidden"  name="model_type" value="{{get_class($object)}}">

<hr>
@if (isset($customProperties))
  <input type="hidden"  name="custom_properties" value="{{ $customProperties}}">
@endif


@if (isset($media))
  @php
    $fileUrl = $media->getUrl();
  @endphp
<div class="form-group">
  <div class="col-sm-12">
    <input type="hidden"  name="media_id" value="{{$media->id}}">
    @if ($media->media_type == 'image')
        <img src="{{ $fileUrl }}" alt="" class='vertical_center'>
    @endif
    @if ($media->media_type == 'video')
      <video controls class='vertical_center'>
        <source src="{{ $fileUrl }}"  type="{{ $media->mime_type }}"/>
        Your browser does not support the video tag.
      </video>
    @endif

    @if ($media->media_type == 'audio')
        <center >
        <br><br>
        <i class="fas fa-file-audio fa-6x"></i>
        <br><br>
        <audio controls >
          <source src="{{ $fileUrl }}"  type="{{ $media->mime_type }}"/>
          </audio>
        </center>
    @endif
  </div>
  </div>
@else
<div class="form-group">
  <div class="col-sm-12">
    <label for="uploaded_media" class="col-sm-3 control-label mandatory">
      @lang('messages.media_file')
    </label>
    <div class="col-sm-6">
    <input type="file" name="uploaded_media" id="uploaded_media" >
    </div>
  </div>
</div>
@endif

<div class="form-group">
<label for="title" class="col-sm-3 control-label">
  @lang('messages.title')
</label>
<a data-toggle="collapse" href="#title_hint" class="btn btn-default">?</a>
<div class="col-sm-6">
  <table class="table table-striped">
    <thead>
      <th>
        @lang('messages.language')
      </th>
      <th>
        @lang('messages.value')
      </th>
    </thead>
    <tbody>
      @foreach ($languages as $language)
        <tr>
          <td>{{$language->name}}</td>
          <td>
            @php
              $mediaTitle =
              old('description.'. $language->id,
              isset($media)
              ? $media->translate(\App\Models\UserTranslation::DESCRIPTION, $language->id)
              : null);
            @endphp
            <textarea name="description[{{$language->id}}]" class='max_width'>{{$mediaTitle}}</textarea>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
</div>

<div class="form-group">
<label for="tags" class="col-sm-3 control-label">
@lang('messages.tags')
</label>
<a data-toggle="collapse" href="#tags_hint" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::select(
    'tags',
    $tags->pluck('name', 'id'), isset($media) ? $media->tags->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
  <div id="tags_hint" class="col-sm-12 collapse">
    @lang('messages.tags_hint')
  </div>
</div>

<hr>

<div class="form-group">
  <label for="collector" class="col-sm-3 control-label" id='authorslabel'>
    @lang('messages.credits')
  </label>
  <a data-toggle="collapse" href="#credits_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    {!! Multiselect::autocomplete('collector',
        $persons->pluck('abbreviation', 'id'),
        isset($media) ? $media->collectors->pluck('person_id') : '',
        ['class' => 'multiselect form-control'])
    !!}
  </div>
  <div id="credits_hint" class="col-sm-12 collapse">
    @lang('messages.credits_hint')
  </div>
</div>


<!-- license object must be an array with CreativeCommons license codes applied to the model --->
<div class="form-group" id='creativecommons' >
    <label for="license" class="col-sm-3 control-label mandatory">
      @lang('messages.public_license')
    </label>
    <a data-toggle="collapse" href="#creativecommons_media_hint" class="btn btn-default">?</a>
    <div class="col-sm-6">
      @php
        $currentLicense = "CC0";
        $currentVersion = config('app.creativecommons_version')[0];
        if (isset($media)) {
           if (null != $media->license) {
             $license = explode(' ',$media->license);
             $currentLicense = $license[0];
             $currentVersion = $license[1];
           }
        }
        $oldLicense = old('license', $currentLicense);
        $oldVersion = old('version',$currentVersion);
        $readonly = null;
        if (count(config('app.creativecommons_version'))==1) {
          $readonly = 'readonly';
        }
      @endphp
      <select name="license" id="license" class="form-control" >
        @foreach (config('app.creativecommons_licenses') as $level)
          <option value="{{ $level }}" {{ $level == $oldLicense ? 'selected' : '' }}>
            {{$level}} - @lang('levels.' . $level)
          </option>
        @endforeach
      </select>
      <strong>version:</strong>
      @if (null != $readonly)
        <input type="hidden" name="license_version" value=" {{ $oldVersion }}">
        {{ $oldVersion }}
      @else
      <select name="license_version" class="form-control" {{ $readonly }}>
        @foreach (config('app.creativecommons_version') as $version)
          <option value="{{ $version }}" {{ $version == $oldVersion ? 'selected' : '' }}>
            {{ $version}}
          </option>
        @endforeach
      </select>
      @endif
    </div>
    <div class="col-sm-12">
      <div id="creativecommons_media_hint" class="panel-collapse collapse">
        <br>
        @lang('messages.creativecommons_media_hint')
      </div>
    </div>
</div>


<!-- PROJECT -->
<div class="form-group">
    <label for="project" class="col-sm-3 control-label">
      @lang('messages.project')
    </label>
    <a data-toggle="collapse" href="#project_hint" class="btn btn-default">?</a>
    <div class="col-sm-6">
       @if (count($projects))
         @php
         $selected = old('project_id', isset($media) ? $media->project_id : (Auth::user()->defaultProject ? Auth::user()->defaultProject->id : null));
         @endphp
	        <select name="project_id" id="project_id" class="form-control" >
            <option value="">@lang('messages.select')</option>
	           @foreach ($projects as $project)
		             <option value="{{$project->id}}" {{ $project->id == $selected ? 'selected' : '' }}>
                   {{ $project->name }}
		             </option>
	           @endforeach
	        </select>
        @else
          <div class="alert alert-warning">
            @lang ('messages.no_registered_projects')
          </div>
        @endif
    </div>
    <div class="col-sm-12">
      <div id="project_hint" class="panel-collapse collapse">
	       @lang('messages.media_project_hint')
       </div>
     </div>
</div>



<div class="form-group" >
  <label for="date" class="col-sm-3 control-label">@lang('messages.date')</label>
  <a data-toggle="collapse" href="#date_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="date" name="date" value="{{ old('date',isset($media) ? $media->date : null) }}" min="1600-01-01" max={{today()}}>
  </div>
  <div id="date_hint" class="col-sm-12 collapse">
    @lang('messages.media_date_hint')
  </div>
</div>


<div class="form-group">
<label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
<div class="col-sm-6">
  <textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($media) ? $media->notes : null) }}</textarea>
</div>
</div>

  <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')
				</button>
				<a href="{{url()->previous()}}" class="btn btn-warning">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.back')
				</a>
			    </div>
			</div>
		    </form>
        </div>
    </div>

<style >

.vertical_center {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 99%;
  width: 99%;
}

.max_width {
  max-width: 100%;
}

</style>

@endsection
@push ('scripts')
{!! Multiselect::scripts('collector', url('persons/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
<script>
$(document).ready(function() {
/* if license is different than public domain, title and authors must be informed */
/* and sui generis database policies may be included */
$('#license').on('change',function() {
    var license = $('#license option:selected').val();
    if (license == "CC0") {
        $('#authorslabel').removeClass('mandatory');
    } else {
        $('#authorslabel').addClass('mandatory');
    }
});

});

</script>

@endpush
