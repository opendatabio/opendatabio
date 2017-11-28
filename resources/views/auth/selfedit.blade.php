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
    <label for="email" class="col-sm-3 control-label">
@lang('messages.email')
</label>
    <div class="col-sm-6">
	<input type="text" name="email" id="email" class="form-control" value="{{ old('email', Auth::user()->email) }}">
    </div>
</div>
<div class="form-group">
    <label for="password" class="col-sm-3 control-label">
@lang('messages.current_password')
</label>
	    <div class="col-sm-6">
	<input type="password" name="password" id="password" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="new_password" class="col-sm-3 control-label">
@lang('messages.new_password')
</label>
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
    <label for="new_password_confirmation" class="col-sm-3 control-label">
@lang('messages.confirm_password')
</label>
	    <div class="col-sm-6">
	<input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" value="">
            </div>
</div>
<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label">
@lang('messages.default_person')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="person_autocomplete" id="person_autocomplete" class="form-control autocomplete"
    value="{{ old('person_autocomplete', (Auth::user()->person ? Auth::user()->person->full_name : null)) }}">
    <input type="hidden" name="person_id" id="person_id"
    value="{{ old('person_id', Auth::user()->person_id) }}">
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
		{{ Auth::user()->textAccess }}
    </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.save')

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
@push ('scripts')
<script>
$(document).ready(function() {
$("#person_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#person_id", "@lang('messages.noresults')");
});
</script>
@endpush
