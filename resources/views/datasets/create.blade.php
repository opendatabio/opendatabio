@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_dataset')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($dataset))
		    <form action="{{ url('datasets/' . $dataset->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('datasets')}}" method="POST" class="form-horizontal">
@endif
		     {{ csrf_field() }}


         <!-- short name and title -->
         <div class="form-group">
             <label for="name" class="col-sm-3 control-label mandatory">
               @lang('messages.name')
             </label>
             <a data-toggle="collapse" href="#dataset_name_hint" class="btn btn-default">?</a>
             <div class="col-sm-6">
         	     <input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($dataset) ? $dataset->name : null) }}" maxlength="50">
             </div>
             <div class="col-sm-12">
               <div id="dataset_name_hint" class="panel-collapse collapse">
                  @lang('messages.dataset_name_hint')
                </div>
              </div>
         </div>

         <div class="form-group">
             <label for="privacy" class="col-sm-3 control-label mandatory">
         @lang('messages.privacy')
         </label>
                 <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
         	    <div class="col-sm-6">
         	<?php $selected = old('privacy', isset($dataset) ? $dataset->privacy : 2); ?>

         	<select name="privacy" id="privacy" class="form-control" >
         	@foreach (App\Models\Dataset::PRIVACY_LEVELS as $level)
                 <option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
         @lang('levels.privacy.' . $level)
         </option>
         	@endforeach
         	</select>
                     </div>
           <div class="col-sm-12">
             <div id="hint1" class="panel-collapse collapse">
         	@lang('messages.dataset_privacy_hint')
             </div>
           </div>
         </div>

         <!-- license object must be an array with CreativeCommons license codes applied to the model --->
         @php
           $show_dataset_viewers = '';
           $license_mandatory = "";

           $currentlicense = "CC-BY";
           $currentversion = config('app.creativecommons_version')[0];
           if (isset($dataset)) {
              if (null != $dataset->license) {
                $license = explode(' ',$dataset->license);
                $currentlicense = $license[0];
                $currentversion = isset($license[1]) ? $license[1] : $currentversion;
              }
           }
           $oldlicense = old('license', $currentlicense);
           $oldversion = old('version',$currentversion);
           $version_readonly = null;
           if (count(config('app.creativecommons_version'))==1) {
             $version_readonly = 'readonly';
           }
         @endphp
         <div class="form-group" id='creativecommons' >
             <label for="license" class="col-sm-3 control-label" id='licenselabel'>
               @lang('messages.public_license')
             </label>
             <a data-toggle="collapse" href="#creativecommons_licenses_hint" class="btn btn-default">?</a>
             <div class="col-sm-6">
               <select name="license" id="license" class="form-control" >
                 @foreach (config('app.creativecommons_licenses') as $level)
                   <option value="{{ $level }}" {{ $level == $oldlicense ? 'selected' : '' }}>
                     {{$level}} - @lang('levels.' . $level)
                   </option>
                 @endforeach
               </select>
               <strong>version:</strong>
               @if (null != $version_readonly)
                 <input type="hidden" name="license_version" value=" {{ $oldversion }}">
                 {{ $oldversion }}
               @else
               <select name="license_version" class="form-control" {{ $version_readonly }}>
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
                 @lang('messages.creativecommons_dataset_hint')
               </div>
             </div>
         </div>

         <!-- following creative commons, filling here implicate the dataset has sui generis database rights, which will be indicated here -->
         <div class="form-group" id='show_policy' >
             <label for="policy" class="col-sm-3 control-label">
               @lang('messages.data_policy')
             </label>
             <a data-toggle="collapse" href="#dataset_policy_hint" class="btn btn-default">?</a>
             <div class="col-sm-6">
         	     <textarea name="policy" id="policy" class="form-control">{{ old('policy', isset($dataset) ? $dataset->policy : null) }}</textarea>
              </div>
             <div class="col-sm-12">
             <div id="dataset_policy_hint" class="panel-collapse collapse">
               @lang('messages.data_policy_hint')
             </div>
           </div>
         </div>


       <div class="form-group">
           <label for="title" class="col-sm-3 control-label" id='titlelabel'>
             @lang('messages.title')
           </label>
           <a data-toggle="collapse" href="#dataset_title_hint" class="btn btn-default">?</a>
           <div class="col-sm-6">
       	     <input type="text" name="title" id="title" class="form-control" value="{{ old('title', isset($dataset) ? $dataset->title : null) }}" maxlength="191">
           </div>
           <div class="col-sm-12">
             <div id="dataset_title_hint" class="panel-collapse collapse">
                @lang('messages.dataset_title_hint')
              </div>
            </div>
       </div>

      <!-- collector -->
      <div class="form-group">
          <label for="authors" class="col-sm-3 control-label" id='authorslabel'>
            @lang('messages.authors')
          </label>
          <a data-toggle="collapse" href="#authors_hint" class="btn btn-default">?</a>
          <div class="col-sm-6">
            {!! Multiselect::autocomplete('authors',
              $persons->pluck('abbreviation', 'id'),
              isset($dataset) ? $dataset->authors->pluck('person_id') :
              (empty(Auth::user()->person_id) ? '' : [Auth::user()->person_id] ),
              ['class' => 'multiselect form-control'])
            !!}
          </div>
          <div class="col-sm-12">
            <div id="authors_hint" class="panel-collapse collapse">
               @lang('messages.dataset_authors_hint')
             </div>
           </div>
      </div>

<!-- PROJECT -->
<div class="form-group">
    <label for="project" class="col-sm-3 control-label" id='projectlabel'>
      @lang('messages.project')
    </label>
    <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <input type="text" name="project_autocomplete" id="project_autocomplete" class="form-control autocomplete"
      value="{{ old('project_autocomplete', (isset($dataset) and $dataset->project_id) ? $dataset->project->name : null) }}">
      <input type="hidden" name="project_id" id="project_id"
      value="{{ old('project_id', isset($dataset) ? $dataset->project_id : null) }}">
    </div>
    <div class="col-sm-12">
      <div id="hint3" class="panel-collapse collapse">
	       @lang('messages.dataset_project_hint')
       </div>
     </div>
</div>


<hr>

<div class="form-group">
    <label for="description" class="col-sm-3 control-label" >
@lang('messages.dataset_short_description')
</label>
<a data-toggle="collapse" href="#dataset_short_description_hint" class="btn btn-default">?</a>
<div class="col-sm-6">
  <textarea name="description" id="description" class="form-control" maxlength="500">{{ old('description', isset($dataset) ? $dataset->description : null) }}</textarea>
</div>
<div class="col-sm-12">
  <div id="dataset_short_description_hint" class="panel-collapse collapse">
@lang('messages.dataset_short_description_hint')
  </div>
</div>
</div>

<!-- TAGS or KEYWORDS -->
<div class="form-group">
<label for="tags" class="col-sm-3 control-label">
@lang('messages.tags')
</label>
<a data-toggle="collapse" href="#tags_hint" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::select(
    'tags',
    $tags->pluck('name', 'id'), isset($dataset) ? $dataset->tags->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
<div class="col-sm-12">
  <div id="tags_hint" class="panel-collapse collapse">
@lang('messages.tags_hint')
  </div>
</div>
</div>


<!-- DATASET METADATA -->
<hr>
<div class="form-group">
  <label for="metadata" class="col-sm-3 control-label">
    @lang('messages.dataset_metadata')
  </label>
  <a data-toggle="collapse" href="#dataset_metadata_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
     <textarea name="metadata" id="metadata" class="form-control">{{ old('metadata', isset($dataset) ? $dataset->metadata : null) }}</textarea>
   </div>
   <div class="col-sm-12">
     <div id="dataset_metadata_hint" class="panel-collapse collapse">
       @lang('messages.dataset_metadata_hint')
     </div>
   </div>
</div>


<!-- DATASET BIBLIOGRAPHIC REFERENCES -->
<hr>
<div class="form-group">
<label for="references" class="col-sm-3 control-label">
@lang('messages.dataset_bibreferences_mandatory')
</label>
<a data-toggle="collapse" href="#hint_bib_mandatory" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::select(
    'references',
    $references->pluck('bibkey', 'id'), isset($dataset) ? $dataset->references->where('mandatory',1)->pluck('bib_reference_id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
<div class="col-sm-12">
  <div id="hint_bib_mandatory" class="panel-collapse collapse">
@lang('messages.dataset_bibreferences_mandatory_hint')
  </div>
</div>
</div>

<div class="form-group">
<label for="references_aditional" class="col-sm-3 control-label">
@lang('messages.dataset_bibreferences_aditional')
</label>
<a data-toggle="collapse" href="#hint_bib_aditional" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::select(
    'references_aditional',
    $references->pluck('bibkey', 'id'), isset($dataset) ? $dataset->references->where('mandatory',0)->pluck('bib_reference_id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
<div class="col-sm-12">
  <div id="hint_bib_aditional" class="panel-collapse collapse">
@lang('messages.dataset_bibreferences_additional_hint')
  </div>
</div>
</div>







<!-- DATASET USER BOX - ONLY AVAILABLE IF Privacy NOT CONTROLLED BY PROJECT-->
<hr>
<div class="form-group userbox">
<label for="admins" class="col-sm-3 control-label mandatory">
@lang('messages.admins')
</label>
<a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'admins',
    $fullusers->pluck('email', 'id'), isset($dataset) ? $dataset->admins->pluck('id') : [Auth::user()->id],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div>
<div class="form-group userbox">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.collabs')
</label>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'collabs',
    $fullusers->pluck('email', 'id'), isset($dataset) ? $dataset->collabs->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div>

<!-- viewers are only meaning_full if the dataset is not of public access -->
<div class="form-group userbox" id='dataset_viewers' {{ $show_dataset_viewers }}>
<label for="viewers" class="col-sm-3 control-label">
@lang('messages.viewers')
</label>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'viewers',
    $allusers->pluck('email', 'id'), isset($dataset) ? $dataset->viewers->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div>
<div class="col-sm-12 userbox">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.dataset_admins_hint')
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
/*$("#bibreference_autocomplete").odbAutocomplete("{{url('references/autocomplete')}}","#bibreference_id", "@lang('messages.noresults')");*/
$("#project_autocomplete").odbAutocomplete("{{url('projects/autocomplete')}}","#project_id", "@lang('messages.noresults')");

/* DEFINE LICENSE FIELDS */
function licenseOptions() {
  var privacy = $('#privacy option:selected').val();
  var license = $('#license option:selected').val();
  if ("undefined" === typeof privacy) {
    return; // nothing to do here...
  }
  var options = <?php
      $values = config('app.creativecommons_licenses');
      $results = [];
      foreach($values as $val) {
        $results[] = [$val,$val." ".Lang::get("levels.".$val)];
      }
      echo json_encode($results);
  ?>;
  switch (privacy) {
    case "{{ App\Models\Dataset::PRIVACY_AUTH}}": //dataset users
    case "{{ App\Models\Dataset::PRIVACY_PROJECT }}": //project users
      options = <?php
          $values = array_values(preg_grep("/ND/",config('app.creativecommons_licenses')));
          $results = [];
          foreach($values as $val) {
            $results[] = [$val,$val." ".Lang::get("levels.".$val)];
          }
          echo json_encode($results);
      ?>;
    case "{{ App\Models\Dataset::PRIVACY_REGISTERED }}": //open access registered users
    default: // other
  }
  $('#license').empty();
  var selected = "";
  options.forEach(function(item, index) {
    //select old if possible
    if (item[0] == license | (null == license & item[0]==options[1][0])) {
      selected = "selected";
    } else {
      selected = "";
    }
    $('#license').append("<option "+selected+" value='"+item[0]+"'>"+item[1]+"</option>");
  });
}

/* Define other form fields */
function setFormFields(vel) {
  var privacy = $('#privacy option:selected').val();
  if ("undefined" === typeof privacy) {
    return; // nothing to do here...
  }
  switch (privacy) {
    case "{{ App\Models\Dataset::PRIVACY_AUTH}}": //dataset users
      $(".userbox").show(vel);
      $('#authorslabel').removeClass('mandatory');
      $('#titlelabel').removeClass('mandatory');
      $('#projectlabel').removeClass('mandatory');
      break;
    case "{{ App\Models\Dataset::PRIVACY_PROJECT }}": //project users
      $(".userbox").hide(vel); //hide users definitions, as inherit from Project
      $('#authorslabel').removeClass('mandatory');
      $('#titlelabel').removeClass('mandatory');
      $('#projectlabel').addClass('mandatory');
      break;
    case "{{ App\Models\Dataset::PRIVACY_REGISTERED }}": //open access registered users
    default: // other
      $(".userbox").show(vel);
      $('#licenselabel').addClass('mandatory');
      $('#authorslabel').addClass('mandatory');
      $('#titlelabel').addClass('mandatory');
      $('#projectlabel').removeClass('mandatory');
      $('#dataset_viewers').hide();
    }
}


    /* show or hide elements depending on type of privacy */
    $('#privacy').on('change',function() {
        setFormFields(400);
        licenseOptions();
    });

    /* set fields on page load */
    setFormFields(0);
    licenseOptions();
});

</script>
{!! Multiselect::scripts('admins', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('collabs', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('viewers', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('authors', url('persons/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
@endpush
