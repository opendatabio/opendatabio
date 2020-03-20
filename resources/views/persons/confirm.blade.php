@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.confirm_person')
                </div>

                <div class="panel-body">
		@lang ('messages.confirm_person_message')
		<br>
		<strong>@lang('messages.full_name')</strong> {{old('full_name')}}
		<strong>@lang('messages.abbreviation')</strong> {{old('abbreviation')}}
		<br>
		@lang ('messages.possible_dupes')
		<br>
			<ul>
		@foreach ( $dupes as $dupe)
			<li>{{$dupe->full_name}} ({{$dupe->abbreviation}})</li>
		@endforeach
			</ul>
		    <!-- Edit Person Form -->
		    <form action="{{ url('persons') }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
<input type="hidden" name="confirm" value="1">
<input type="hidden" name="full_name" id="full_name" value="{{ old('full_name') }}">
<input type="hidden" name="abbreviation" id="abbreviation" value="{{ old('abbreviation') }}">
<input type="hidden" name="email" id="email" value="{{ old('email') }}">
<input type="hidden" name="institution" id="institution" value="{{ old('institution') }}">

<input type="hidden" name="notes" id="notes" value="{{ old('notes')}}">

<input type="hidden" name="herbarium_id" value="{{ old('herbarium_id') }}">
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.confirm_person')

				</button>
			    </div>
			</div>
		    </form>

                </div>
	    </div>
<!-- Other details (specialist, collects, etc?) -->
        </div>
    </div>
@endsection
