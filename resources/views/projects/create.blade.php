@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_project')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($project))
		    <form action="{{ url('projects/' . $project->id)}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('projects')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
@endif
		     {{ csrf_field() }}

<div class="form-group">
    <label for="name" class="col-sm-3 control-label mandatory">
@lang('messages.name')
</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($project) ? $project->name : null) }}">
    </div>
</div>


<div class="form-group">
    <label for="description" class="col-sm-3 control-label">
      @lang('messages.dataset_short_description')
    </label>
    <a data-toggle="collapse" href="#dataset_short_description" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <textarea name="description" id="description" class="form-control" maxlength="500">{{ old('description', isset($project) ? $project->description : null) }}</textarea>
    </div>
    <div class="col-sm-12">
      <div id="dataset_short_description" class="panel-collapse collapse">
  	@lang('messages.project_short_description_hint')
      </div>
    </div>
</div>


<div class="form-group">
    <label for="details" class="col-sm-3 control-label">
      @lang('messages.project_details')
    </label>
    <a data-toggle="collapse" href="#dataset_details" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <textarea name="details" id="details" class="form-control">{{ old('details', isset($project) ? $project->details : null) }}</textarea>
    </div>
    <div class="col-sm-12">
      <div id="dataset_details" class="panel-collapse collapse">
  	@lang('messages.project_details_hint')
      </div>
    </div>
</div>


<div class="form-group">
  <label for="logo" class="col-sm-3 control-label ">
      @lang('messages.logo')
  </label>
  <a data-toggle="collapse" href="#logo_hint" class="btn btn-default">?</a>
  <div class="col-sm-4">
    <input type="file" name="logo" id="logo"  >
  </div>
  <div class="col-sm-2">
    @if(isset($logo))
      <div class="float-right">
        <img src='{{ url($logo) }}' width='100'>
      </div>
    @endif
  </div>
  <div class="col-sm-12">
    <div id="logo_hint" class="panel-collapse collapse">
@lang('messages.logo_project_hint')
    </div>
  </div>
</div>



<div class="form-group">
  <label for="tags" class="col-sm-3 control-label ">
      @lang('messages.tags')
  </label>
  <a data-toggle="collapse" href="#tags_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    {!! Multiselect::select(
        'tags',
        $tags->pluck('name', 'id'), isset($project) ? $project->tags->pluck('id') : [],
         ['class' => 'multiselect form-control']
    ) !!}
  </div>
  <div class="col-sm-12">
    <div id="tags_hint" class="panel-collapse collapse">
@lang('messages.tags_hint')
    </div>
  </div>
</div>



<hr>

<div class="form-group">
    <label for="privacy" class="col-sm-3 control-label mandatory">
@lang('messages.privacy')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('privacy', isset($project) ? $project->privacy : 0); ?>

	<select name="privacy" id="privacy" class="form-control" >
	@foreach (App\Models\Project::PRIVACY_LEVELS as $level)
        <option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
@lang('levels.privacy.' . $level)
</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.project_privacy_hint')
    </div>
  </div>
</div>


<!-- license object must be an array with CreativeCommons license codes applied to the model --->
@php
  $cc_mandatory = '';
  $show_project_viewers = '';
  $privacy = old('privacy', isset($project) ? $project->privacy : 0);
  if ($privacy!=App\Models\Dataset::PRIVACY_AUTH) {
    $cc_mandatory = 'mandatory';
    $show_project_viewers = 'hidden';
  }


@endphp
<div class="form-group" id='creativecommons'>
    <label for="license" class="col-sm-3 control-label {{ $cc_mandatory }} " id='licenselabel'>
      @lang('messages.public_license')
    </label>
    <a data-toggle="collapse" href="#creativecommons_licenses_hint" class="btn btn-default">?</a>
    <div class="col-sm-6">
      @php
        $currentlicense = "CC0";
        $currentversion = config('app.creativecommons_version')[0];
        if (isset($project)) {
           if (null != $project->license) {
             $license = explode(' ',$project->license);
             $currentlicense = $license[0];
             $currentversion = $license[1];
           }
        }
        $oldlicense = old('license', $currentlicense);
        $oldversion = old('version',$currentversion);
        $readonly = null;
        if (count(config('app.creativecommons_version'))==1) {
          $readonly = 'readonly';
        }
        $title_mandatory = null;
        $show_policy = 'hidden';
        if ($oldlicense != "CC0") {
          $title_mandatory = 'mandatory';
          $show_policy = '';
        }

      @endphp
      <select name="license" id="license" class="form-control" >
        @foreach (config('app.creativecommons_licenses') as $level)
          <option value="{{ $level }}" {{ $level == $oldlicense ? 'selected' : '' }}>
            {{$level}} - @lang('levels.' . $level)
          </option>
        @endforeach
      </select>
      <strong>version:</strong>
      @if (null != $readonly)
        <input type="hidden" name="license_version" value=" {{ $oldversion }}">
        {{ $oldversion }}
      @else
      <select name="license_version" class="form-control" {{ $readonly }}>
        @foreach (config('app.creativecommons_version') as $version)
          <option value="{{ $version }}" {{ $version == $oldversion ? 'selected' : '' }}>
            {{ $version}}
          </option>
        @endforeach
      </select>
      @endif
    </div>
    <div class="col-sm-12">
      <div id="creativecommons_licenses_hint" class="panel-collapse collapse">
        <br>
        @lang('messages.creativecommons_project_hint')
      </div>
    </div>
</div>

<!-- title is, like for datasets, to generate citations. For occurrence data within projects -->
<div class="form-group">
    <label for="title" class="col-sm-3 control-label {{ $title_mandatory }}" id='titlelabel'>
      @lang('messages.title')
    </label>
    <a data-toggle="collapse" href="#project_title_hint" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <input type="text" name="title" id="title" class="form-control" value="{{ old('title', isset($project) ? $project->title : null) }}" maxlength="191">
    </div>
    <div class="col-sm-12">
      <div id="project_title_hint" class="panel-collapse collapse">
         @lang('messages.project_title_hint')
       </div>
     </div>
</div>


<!-- title is, like for datasets, to generate citations. For occurrence data within projects -->
<div class="form-group">
  <label for="authors" class="col-sm-3 control-label {{ $title_mandatory}}" id='authorslabel'>
  @lang('messages.authors')
  </label>
  <a data-toggle="collapse" href="#authors_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    {!! Multiselect::autocomplete('authors',
    $persons->pluck('abbreviation', 'id'),
    isset($project->authors) ? $project->authors->pluck('person_id') : '',
    ['class' => 'multiselect form-control'])
    !!}
  </div>
  <div class="col-sm-12">
    <div id="authors_hint" class="panel-collapse collapse">
      @lang('messages.project_authors_hint')
    </div>
  </div>
</div>

<!-- following creative commons, filling here implicate the dataset has sui generis database rights, which will be indicated here -->
<div class="form-group" id='show_policy' {{ $show_policy }}>
    <label for="policy" class="col-sm-3 control-label">
      @lang('messages.data_policy')
    </label>
    <a data-toggle="collapse" href="#data_policy" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <textarea name="policy" id="policy" class="form-control">{{ old('policy', isset($project) ? $project->policy : null) }}</textarea>
     </div>
    <div class="col-sm-12">
    <div id="data_policy" class="panel-collapse collapse">
      @lang('messages.data_policy_hint')
    </div>
  </div>
</div>

<hr>

<div class="form-group">
<label for="admins" class="col-sm-3 control-label mandatory">
@lang('messages.admins')
</label>
<a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'admins',
    $fullusers->pluck('email', 'id'), isset($project) ? $project->admins->pluck('id') : [Auth::user()->id],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.collabs')
</label>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'collabs',
    $fullusers->pluck('email', 'id'), isset($project) ? $project->collabs->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div>

<div class="form-group" id='project_viewers' {{ $show_project_viewers }} >
  <label for="collabs" class="col-sm-3 control-label">
    @lang('messages.viewers')
  </label>
  <div class="col-sm-6">
    {!! Multiselect::autocomplete(
      'viewers',
      $allusers->pluck('email', 'id'), isset($project) ? $project->viewers->pluck('id') : [],
      ['class' => 'multiselect form-control']
      ) !!}
    </div>
    <div class="col-sm-12">
      <div id="hint2" class="panel-collapse collapse">
	       @lang('messages.project_admins_hint')
       </div>
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
@endsection
@push ('scripts')
<script>
$(document).ready(function() {

/* show or hide elements depending on type of privacy */
$('#privacy').on('change',function() {
    var privacy = $('#privacy option:selected').val();
    if (privacy == {{ App\Models\Dataset::PRIVACY_AUTH}}) {
        $('#project_viewers').show();
        $('#licenselabel').removeClass('mandatory');

        //$('#titlelabel').removeClass('mandatory');
        //$('#authorslabel').removeClass('mandatory');
    } else {
        //$('#authorslabel').addClass('mandatory');
        $('#licenselabel').addClass('mandatory');
        //$('#titlelabel').addClass('mandatory');
        $('#project_viewers').hide();
    }
});


/* if license is different than public domain, title and authors must be informed */
/* and sui generis database policies may be included */
$('#license').on('change',function() {
    var license = $('#license option:selected').val();
    if (license == "CC0") {
        $('#titlelabel').removeClass('mandatory');
        $('#authorslabel').removeClass('mandatory');
        $('#policy').val(null);
        $('#show_policy').hide();
    } else {
        $('#authorslabel').addClass('mandatory');
        $('#titlelabel').addClass('mandatory');
        $('#show_policy').show();
    }
});


});

</script>


{!! Multiselect::scripts('authors', url('persons/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('admins', url('users/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('collabs', url('users/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('viewers', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
@endpush
