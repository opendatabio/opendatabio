@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.edit_profile')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

                    <!-- Edit Person Form -->
		    <form action="{{ url('selfupdate') }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('PUT') }}

<div class="form-group">
    <label for="email" class="col-sm-3 control-label">@lang('messages.email')</label>
    <div class="col-sm-6">
	<input type="text" name="email" id="email" class="form-control" value="{{ old('email', Auth::user()->email) }}">
    </div>
</div>
<div class="form-group">
    <label for="password" class="col-sm-3 control-label">@lang('messages.current_password')</label>
	    <div class="col-sm-6">
	<input type="password" name="password" id="password" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="new_password" class="col-sm-3 control-label">@lang('messages.new_password')</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="password" name="new_password" id="new_password" class="form-control" value="">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.password_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="new_password_confirmation" class="col-sm-3 control-label">@lang('messages.confirm_password')</label>
	    <div class="col-sm-6">
	<input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label">@lang('messages.default_person')</label>
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
	@lang('messages.hint_default_person')
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
				    <i class="fa fa-btn fa-plus"></i>@lang('messages.save')
				</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
