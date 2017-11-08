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
@lang('messages.vouchers_hint')
      </div>
    </div>
  </div>

@if (isset($object)) <!-- we're inside a Location, Project or Taxon view -->
        <div class="panel panel-default">
            <div class="panel-heading">
  @lang('messages.voucher_list')
            </div>
            <div class="panel-body">
    <p><strong>
    @lang('messages.voucher_list_for'):</strong>
    {{ $object->fullname }}
    </p>
            </div>
        </div>
@endif
            <!-- Registered Vouchers -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.vouchers')
                    </div>
{!! $dataTable->table() !!}
                    <div class="panel-body">
                    </div>
                </div>
        </div>
    </div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
