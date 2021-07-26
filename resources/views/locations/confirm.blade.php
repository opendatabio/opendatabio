@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.confirm_location')
                </div>

                <div class="panel-body">
		@lang ('messages.confirm_location_message')
		<br>
        <strong>@lang('messages.inserting')</strong>
    <br>
		{{ old('parent_autocomplete') . " > " . old('name')}}
    <br><br>
		@lang ('messages.possible_dupes')
		<br>
			<ul>
		@foreach ( $dupes as $dupe)
			<li>{!! $dupe->rawLink() !!}</li>
		@endforeach
			</ul>
		    <!-- Edit Person Form -->
		    <form action="{{ url('locations') }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
<input type="hidden" name="confirm" value="1">
<input type="text" name="name" id="name" value="{{ old('name') }}">
<input type="text" name="adm_level" id="adm_level" value="{{ old('adm_level') }}">
<input type="text" name="geom" id="geom" value="{{ old('geom') }}">
<input type="text" name="lat1" id="lat1" value="{{ old('lat1') }}">
<input type="text" name="lat2" id="lat2" value="{{ old('lat2') }}">
<input type="text" name="lat3" id="lat3" value="{{ old('lat3') }}">
<input type="text" name="latO" id="latO" value="{{ old('latO') }}">
<input type="text" name="long1" id="long1" value="{{ old('long1') }}">
<input type="text" name="long2" id="long2" value="{{ old('long2') }}">
<input type="text" name="long3" id="long3" value="{{ old('long3') }}">
<input type="text" name="longO" id="longO" value="{{ old('longO') }}">
<input type="text" name="geom_type" id="geom_type" value="{{ old('geom_type') }}">
<input type="text" name="x" id="x" value="{{ old('x') }}">
<input type="text" name="y" id="y" value="{{ old('y') }}">
<input type="hidden" name="startx" id="startx" value="{{ old('startx') }}">
<input type="hidden" name="starty" id="starty" value="{{ old('starty') }}">
<input type="text" name="parent_id" id="parent_id" value="{{ old('parent_id') }}">
<input type="hidden" name="uc_id" id="uc_id" value="{{ old('uc_id') }}">
<input type="hidden" name="altitude" id="altitude" value="{{ old('altitude') }}">
<input type="hidden" name="datum" id="datum" value="{{ old('datum') }}">
<input type="hidden" name="notes" id="notes" value="{{ old('notes') }}">
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.confirm_location')
				</button>
			    </div>
			</div>
		    </form>
                </div>
	    </div>
        </div>
    </div>
@endsection
