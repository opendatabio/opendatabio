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
	@lang('messages.person_hint')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.new_person')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

		    <form action="{{ url('persons')}}" method="POST" class="form-horizontal">
                     {{ csrf_field() }}
			@include('persons.form')
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

            <!-- Registered Persons -->
                <div class="panel panel-default">
                    <div class="panel-heading">
@lang('messages.registered_persons')
                    </div>


{!! $dataTable->table() !!}
                </div>
        </div>
    </div>
@endsection


@push('scripts')
{!! $dataTable->scripts() !!}
@endpush
