@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-1 col-sm-10">
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
	@lang('messages.history_hint')
      </div>
    </div>
  </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
@lang('messages.registered_history')

@if (isset($object)) <!-- we're inside a Location, Project or Taxon view -->
      @lang('messages.for')
      <strong>
        {{ class_basename($object) }}
      </strong>
      @if (null !== $object->rawLink())
          {!! $object->rawLink() !!}
      @else
          &nbsp;{{ $object->full_name }}
      @endif
      @if (class_basename($object) == 'Measurement')
          &nbsp; @lang('messages.for') {{ $object->measured->fullname }}
      @endif
@endif

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
