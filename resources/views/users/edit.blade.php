@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.edit_user')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

                    <!-- Edit Person Form -->
		    <form action="{{ url('users/'.$user->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('PUT') }}

<div class="form-group">
    <label for="email" class="col-sm-3 control-label">
@lang('messages.email')
</label>
    <div class="col-sm-6">
	<input type="text" name="email" id="email" class="form-control" value="{{ old('email', isset($user) ? $user->email : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="password" class="col-sm-3 control-label">
@lang('messages.password')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="password" id="password" class="form-control" value="">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.password_change_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="access" class="col-sm-3 control-label">Access Level</label>
    <div class="col-sm-6">
	<?php $selected = old('access_level', $user->access_level); ?>
	<select name="access_level" id="access_level" class="form-control" >
	@foreach ([0,1,2] as $al) 
		<option value="{{$al}}" {{ $al == $selected ? 'selected' : '' }}>
			 @lang ('levels.access.' . $al)
		</option>
	@endforeach
	</select>
    </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.save')

				</button>
			    </div>
			</div>
		    </form>
		    <form action="{{ url('users/'.$user->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('DELETE') }}
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.remove_user')

				</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
