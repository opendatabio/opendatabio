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
@lang('messages.measurements_hint')
      </div>
    </div>
  </div>

@if (isset($object))
@can ('create', App\Measurement::class)
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.create_measurement')
                </div>

                <div class="panel-body">
			    <div class="col-sm-6">
				<a href="{{url ( $object->typename . '/' . $object->id .   '/measurements/create')}}" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create')
				</a>
			</div>
                </div>
	    </div>
@endcan
@endif
            <!-- Registered Vouchers -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.measurements')
                    </div>

                    <div class="panel-body">
<p><strong>
@lang('messages.measurements_for')
:</strong>
@if (isset($object))
{!! $object->rawLink(true) !!}
@elseif (isset($dataset))
{!! $dataset->rawLink() !!}
@else <!-- the only left object is trait -->
{!! $odbtrait->rawLink() !!}
@endif
</p>
{!! $dataTable->table() !!}
        </div>
    </div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
