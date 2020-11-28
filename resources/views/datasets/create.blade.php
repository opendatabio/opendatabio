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
<div class="form-group">
    <label for="name" class="col-sm-3 control-label mandatory">
@lang('messages.name')
</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($dataset) ? $dataset->name : null) }}">
    </div>
</div>



<div class="form-group">
    <label for="description" class="col-sm-3 control-label">
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

<hr>

<div class="form-group">
    <label for="privacy" class="col-sm-3 control-label mandatory">
@lang('messages.privacy')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('privacy', isset($dataset) ? $dataset->privacy : 0); ?>

	<select name="privacy" id="privacy" class="form-control" >
	@foreach (App\Dataset::PRIVACY_LEVELS as $level)
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


<div class="form-group">
    <label for="policy" class="col-sm-3 control-label">
@lang('messages.dataset_policy')
</label>
<a data-toggle="collapse" href="#dataset_policy_hint" class="btn btn-default">?</a>
<div class="col-sm-6">
	<textarea name="policy" id="policy" class="form-control">{{ old('policy', isset($dataset) ? $dataset->policy : null) }}</textarea>
</div>
<div class="col-sm-12">
  <div id="dataset_policy_hint" class="panel-collapse collapse">
@lang('messages.dataset_policy_hint')
  </div>
</div>
</div>

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

<hr>

<div class="form-group">
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
</div><div class="form-group">
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
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.viewers')
</label>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'viewers',
    $allusers->pluck('email', 'id'), isset($dataset) ? $dataset->viewers->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
<div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.dataset_admins_hint')
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
    /*$("#bibreference_autocomplete").odbAutocomplete("{{url('references/autocomplete')}}","#bibreference_id", "@lang('messages.noresults')");*/
});
</script>
{!! Multiselect::scripts('admins', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('collabs', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('viewers', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
@endpush
