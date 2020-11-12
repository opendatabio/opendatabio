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
<p>
@if (isset($object))
  <strong>
    @lang('messages.measurements_for')
    {{ class_basename($object )}}
  </strong>
  {!! $object->rawLink(true) !!}
@elseif (isset($dataset))
  <strong>
    @lang('messages.measurements_for')
    {{ class_basename($dataset)}}
  </strong>
  {!! $dataset->rawLink() !!}
@elseif(isset($odbtrait))
  <strong>
    @lang('messages.measurements_for')
    {{ class_basename($odbtrait)}}
  </strong>
  {!! $odbtrait->rawLink() !!}
@else
  <strong>
  @lang('messages.measurements')
  </strong>
@endif
</p>
</div>
  <div class="panel-body">
    {!! $dataTable->table() !!}
  </div>
</div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
