@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.api_token')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')
<p>
@lang ('messages.token_help')</p>
<p><strong>
@lang('messages.api_token') :</strong>
<span class="large"> {{ Auth::user()->api_token }} </span> </p>

<p>
@lang ('messages.reset_token_help')
                    <!-- Edit Person Form -->
		    <form action="{{ url('token') }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
<div class="form-group">
    <label for="password" class="col-sm-3 control-label">
@lang('messages.current_password')
</label>
	    <div class="col-sm-6">
	<input type="password" name="password" id="password" class="form-control" value="">
            </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.reset')

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
        </div>
    </div>
@endsection
