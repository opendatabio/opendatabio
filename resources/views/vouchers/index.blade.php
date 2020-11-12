@extends('layouts.app')

@section('content')
    <div class="container-fluid">
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
@lang('messages.vouchers_hint')
      </div>
    </div>
  </div>

@if (isset($object))
@can ('create', App\Voucher::class)
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.create_voucher')
                </div>

                <div class="panel-body">
			    <div class="col-sm-6">
				<a href="{{url ( $object->typename . '/' . $object->id .   '/vouchers/create')}}" class="btn btn-success">
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
                        @if (isset($object)) <!-- we're inside a Location, Project or Taxon view -->
                            @lang('messages.voucher_list_for')<strong> {{ class_basename($object) }}</strong>
                            {!! $object->rawLink() !!}
                        @else
                          @lang('messages.vouchers')
                        @endif
                    </div>
                    <div class="panel-body">
                      {!! $dataTable->table([],true) !!}
                    </div>
                </div>
        </div>
    </div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
