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
@can ('create', App\Models\Person::class)
            <div class="panel panel-default">
                <div class="panel-heading">
      <a data-toggle="collapse" href="#createform" class="btn btn-success"><i class="fa fa-btn fa-plus"></i>
  @lang('messages.new_person')
      </a>
                </div>

                <div class="panel-collapse collapse" id='createform'>
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
@endcan

            <!-- Registered Persons -->
                <div class="panel panel-default">
                    <div class="panel-heading">
@lang('messages.registered_persons')
                    </div>


                    <div class="panel-body">
{!! $dataTable->table() !!}
                </div>
        </div>
    </div>
@endsection


@push('scripts')
{!! $dataTable->scripts() !!}
@endpush
