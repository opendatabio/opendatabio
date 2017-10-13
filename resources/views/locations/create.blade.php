@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
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
	@foreach (App\Location::AdmLevels() as $level)
		<option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
			@lang ('levels.adm.' . $level )
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint5" class="panel-collapse collapse">
	@lang('messages.location_adm_level_hint')
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
    <label for="lat1" class="col-sm-3 control-label">
@lang('messages.latitude')
</label>
        <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="lat1" id="lat1" class="form-control latlongpicker" value="{{ old('lat1', isset($location) ? $location->lat1 : null) }}"><span style="font-size: 140%">&#176;</span>
	<input type="text" name="lat2" id="lat2" class="form-control latlongpicker" value="{{ old('lat2', isset($location) ? $location->lat2 : null) }}">'
	<input type="text" name="lat3" id="lat3" class="form-control latlongpicker" value="{{ old('lat3', isset($location) ? $location->lat3 : null) }}">''
	<input type="radio" name="latO" value="1" @if (old('latO', isset($location) ? $location->latO : 1)) checked @endif > N
&nbsp;

	<input type="radio" name="latO" value="0" @if (!old('latO', isset($location) ? $location->latO : 1)) checked @endif > S

            </div>
<br>
    <label for="lat1" class="col-sm-3 control-label">
@lang('messages.longitude')
</label>
	    <div class="col-sm-6">
	<input type="text" name="long1" id="long1" class="form-control latlongpicker" value="{{ old('long1', isset($location) ? $location->long1 : null) }}"><span style="font-size: 140%">&#176;</span> 
	<input type="text" name="long2" id="long2" class="form-control latlongpicker" value="{{ old('long2', isset($location) ? $location->long2 : null) }}">'
	<input type="text" name="long3" id="long3" class="form-control latlongpicker" value="{{ old('long3', isset($location) ? $location->long3 : null) }}">''
	<input type="radio" name="longO" value="1" @if (old('longO', isset($location) ? $location->longO : 1)) checked @endif > E
&nbsp;

	<input type="radio" name="longO" value="0" @if (!old('longO', isset($location) ? $location->longO : 1)) checked @endif > W

            </div>
  <div class="col-sm-12">
    <div id="hint6" class="panel-collapse collapse">
	@lang('messages.latlong_hint')
    </div>
  </div>
</div>
</div>

<div id="super-x">
<div class="form-group">
    <label for="x" class="col-sm-3 control-label">
@lang('messages.dimensions')
</label>
        <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	X: <input type="text" name="x" id="x" class="form-control latlongpicker" value="{{ old('x', isset($location) ? $location->x : null) }}">(m)&nbsp;
	Y: <input type="text" name="y" id="y" class="form-control latlongpicker" value="{{ old('y', isset($location) ? $location->y : null) }}">(m)
            </div>
    <label for="lat1" class="col-sm-3 control-label">
@lang('messages.start')
</label>
	    <div class="col-sm-6">
	Start-X: <input type="text" name="startx" id="startx" class="form-control latlongpicker" value="{{ old('startx', isset($location) ? $location->startx : null) }}">(m)&nbsp;
	Start-Y: <input type="text" name="starty" id="starty" class="form-control latlongpicker" value="{{ old('starty', isset($location) ? $location->starty : null) }}">(m)

            </div>
  <div class="col-sm-12">
    <div id="hint7" class="panel-collapse collapse">
	@lang('messages.dimensions_hint')
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
    <input type="text" name="parent_autocomplete" id="parent_autocomplete" class="form-control autocomplete"
    value="{{ old('parent_autocomplete', (isset($location) and $location->parent) ? $location->parent->fullname : null) }}">
    <input type="hidden" name="parent_id" id="parent_id"
    value="{{ old('parent_id', isset($location) ? $location->parent_id : null) }}">
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
    <input type="text" name="uc_autocomplete" id="uc_autocomplete" class="form-control autocomplete"
    value="{{ old('uc_autocomplete', (isset($location) and $location->uc) ? $location->uc->fullname : null) }}">
    <input type="hidden" name="uc_id" id="uc_id"
    value="{{ old('uc_id', isset($location) ? $location->uc_id : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint4" class="panel-collapse collapse">
	@lang('messages.location_uc_hint')
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
				<a href="{{url()->previous()}}" class="btn btn-warning">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.back')
				</a>
			    </div>
			</div>
		    </form>
			@if (isset($location))
			@can ('delete', $location)
		    <form action="{{ url('locations/'.$location->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('DELETE') }}
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.remove_location')

				</button>
			    </div>
			</div>
		    </form>
		    @endcan <!-- end can delete -->
		    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
@push ('scripts')
<script>
$("#parent_autocomplete").devbridgeAutocomplete({
    serviceUrl: "{{url('locations/autocomplete')}}",
    params: {'scope':  'exceptucs'},
    onSelect: function (suggestion) {
        $("#parent_id").val(suggestion.data);
    },
    onInvalidateSelection: function() {
        $("#parent_id").val(null);
    }
    });
$("#uc_autocomplete").devbridgeAutocomplete({
    serviceUrl: "{{url('locations/autocomplete')}}",
    params: {'scope':  'ucs'},
    onSelect: function (suggestion) {
        $("#uc_id").val(suggestion.data);
    },
    onInvalidateSelection: function() {
        $("#uc_id").val(null);
    }
    });
</script>
@endpush
