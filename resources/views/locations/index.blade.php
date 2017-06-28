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
	@lang('messages.location_hint')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.new_location')
                </div>

                <div class="panel-body">
			    <div class="col-sm-6">
				<a href="{{url ('locations/create')}}" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create')

				</a>
			</div>
                </div>
            </div>

            <!-- Registered Locations -->
                <div class="panel panel-default">
                    <div class="panel-heading">
@lang('messages.registered_locations')
                    </div>


{!! $dataTable->table() !!}
                </div>
        </div>
    </div>
@endsection


@push('scripts')
{!! $dataTable->scripts() !!}
@endpush
