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
    <label for="description" class="col-sm-3 control-label">
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



<div class="form-group">
  <label for="url" class="col-sm-3 control-label ">
      URL
  </label>
  <a data-toggle="collapse" href="#url_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="url" name="url" id="url"  placeholder="https://example.com" >
  </div>
  <div class="col-sm-12">
    <div id="url_hint" class="panel-collapse collapse">
@lang('messages.project_url_hint')
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
	@foreach (App\Project::PRIVACY_LEVELS as $level)
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
</div><div class="form-group">
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
{!! Multiselect::scripts('admins', url('users/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('collabs', url('users/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('viewers', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
@endpush
