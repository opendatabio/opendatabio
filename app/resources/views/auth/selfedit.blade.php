@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Edit Profile
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

                    <!-- Edit Person Form -->
		    <form action="{{ url('selfupdate') }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('PUT') }}

<div class="form-group">
    <label for="email" class="col-sm-3 control-label">E-mail</label>
    <div class="col-sm-6">
	<input type="text" name="email" id="email" class="form-control" value="{{ old('email', Auth::user()->email) }}">
    </div>
</div>
<div class="form-group">
    <label for="password" class="col-sm-3 control-label">Current Password</label>
	    <div class="col-sm-6">
	<input type="password" name="password" id="password" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="new_password" class="col-sm-3 control-label">New Password</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="password" name="new_password" id="new_password" class="form-control" value="">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	Use this field to change your password. Leave it blank if you don't want to edit it.
    </div>
  </div>
</div>
<div class="form-group">
    <label for="new_password_confirmation" class="col-sm-3 control-label">Confirm New Password</label>
	    <div class="col-sm-6">
	<input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label">Default Person</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('person_id', Auth::user()->person_id); ?>

	<select name="person_id" id="person_id" class="form-control" >
		<option value=0>&nbsp;</option>
	@foreach ($persons as $person)
		<option value="{{$person->id}}" {{ $person->id == $selected ? 'selected' : '' }}>{{$person->abbreviation}}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	If you select a Person here, this will be used as the default Person on all relevant forms, such as
	when filling in vouchers or plants collected. You should probably set this to your own name.
    </div>
  </div>
</div>
<div class="form-group">
    <label for="access" class="col-sm-3 control-label">Access Level</label>
    <div class="col-sm-6">
		To be implemented... (READ ONLY)
    </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>Save Changes
				</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
