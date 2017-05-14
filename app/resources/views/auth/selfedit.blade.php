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
    <label for="password" class="col-sm-3 control-label">Old Password</label>
	    <div class="col-sm-6">
	<input type="text" name="oldpassword" id="oldpassword" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="password" class="col-sm-3 control-label">New Password</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="password" id="password" class="form-control" value="">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	Use this field to change your password. Leave it blank if you don't want to edit it.
    </div>
  </div>
</div>
<div class="form-group">
    <label for="password" class="col-sm-3 control-label">Confirm Password</label>
	    <div class="col-sm-6">
	<input type="text" name="confirmpassword" id="confirmpassword" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="access" class="col-sm-3 control-label">Access Level</label>
    <div class="col-sm-6">
		To be implemented...
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
