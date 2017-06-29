@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">
@lang('messages.help')
</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
	@lang('messages.location_create_hint')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.new_location')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($location))
		    <form action="{{ url('locations/' . $location->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('locations')}}" method="POST" class="form-horizontal">
@endif

                     {{ csrf_field() }}
<div class="form-group">
    <label for="name" class="col-sm-3 control-label">
@lang('messages.location_name')
</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($location) ? $location->name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="adm_level" class="col-sm-3 control-label">
@lang('messages.adm_level')
</label>
        <a data-toggle="collapse" href="#hint5" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('adm_level', isset($location) ? $location->adm_level : null); ?>

	<select name="adm_level" id="adm_level" class="form-control" >
		<option value='' {{ is_null($selected) ? 'selected' : '' }} >&nbsp;</option>
	@foreach (config('adm_levels') as $key => $uc)
		<option value="{{$key}}" {{ $key === $selected ? 'selected' : '' }}>{{ $uc }}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.location_parent_hint')
    </div>
  </div>
</div>
<div id="super-geometry">
<div class="form-group">
    <label for="geom" class="col-sm-3 control-label">
@lang('messages.geometry')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<textarea name="geom" id="geom" class="form-control">{{ old('geom', isset($location) ? $location->geom : null) }}</textarea>
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.geom_hint')
    </div>
  </div>
</div>
</div>
<div id="super-points">
<div class="form-group">
    <label for="lat" class="col-sm-3 control-label">
@lang('messages.latitude')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="lat1" id="lat1" class="form-control latlongpicker" value="{{ old('lat1', isset($location) ? $location->lat1 : null) }}">&#176;
	<input type="text" name="lat2" id="lat2" class="form-control latlongpicker" value="{{ old('lat2', isset($location) ? $location->lat2 : null) }}">'
	<input type="text" name="lat3" id="lat3" class="form-control latlongpicker" value="{{ old('lat3', isset($location) ? $location->lat3 : null) }}">''
	<input type="radio" name="latO" value="1" @if (old('latO', isset($location) ? $location->latO : 1)) checked @endif > N
&nbsp;

	<input type="radio" name="latO" value="0" @if (!old('latO', isset($location) ? $location->latO : 1)) checked @endif > S

            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.geom_hint')
    </div>
  </div>
</div>
</div>

<div class="form-group">
    <label for="altitude" class="col-sm-3 control-label">
@lang('messages.altitude')
</label>
	    <div class="col-sm-6">
	<input type="text" name="altitude" id="altitude" class="form-control" value="{{ old('altitude', isset($location) ? $location->altitude : null) }}">
    </div>
</div>
<div class="form-group">
        <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
    <label for="datum" class="col-sm-3 control-label">
@lang('messages.datum')
</label>
	    <div class="col-sm-6">
	<input type="text" name="datum" id="datum" class="form-control" value="{{ old('datum', isset($location) ? $location->datum : null) }}">
    </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.datum_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="parent_id" class="col-sm-3 control-label">
@lang('messages.location_parent')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('parent_id', isset($location) ? $location->parent_id : -1); ?>

	<select name="parent_id" id="parent_id" class="form-control" >
		<!--option value='-1' {{ $selected == -1 ? 'selected' : '' }}>Autodetect</option-->
		<option value='' {{ $selected == 0 ? 'selected' : '' }} >None</option>
	@foreach ($locations as $parentloc)
		<option value="{{$parentloc->id}}" {{ $parentloc->id == $selected ? 'selected' : '' }}>{{ $parentloc->name }}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.location_parent_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="uc_id" class="col-sm-3 control-label">
@lang('messages.location_uc')
</label>
        <a data-toggle="collapse" href="#hint4" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('uc_id', isset($location) ? $location->uc_id : -1); ?>

	<select name="uc_id" id="uc_id" class="form-control" >
		<!--option value='-1' {{ $selected == -1 ? 'selected' : '' }}>Autodetect</option-->
		<option value='' {{ $selected == 0 ? 'selected' : '' }} >None</option>
	@foreach ($uc_list as $uc)
		<option value="{{$uc->id}}" {{ $uc->id == $selected ? 'selected' : '' }}>{{ $uc->name }}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.location_parent_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($location) ? $location->notes : null) }}</textarea>
    </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')

				</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
