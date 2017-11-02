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
@lang('messages.plants_hint')
      </div>
    </div>
  </div>

@if (isset($object)) <!-- we're inside a Location, Project or Taxon view -->
        <div class="panel panel-default">
            <div class="panel-heading">
  @lang('messages.plant_list')
            </div>
            <div class="panel-body">
    <p><strong>
    @lang('messages.plant_list_for'):</strong>
    {{ $object->fullname }}
    </p>
            </div>
        </div>


@else
@can ('create', App\Plant::class)
        <div class="panel panel-default">
            <div class="panel-heading">
  @lang('messages.create_plant')
            </div>

            <div class="panel-body">
            <div class="col-sm-6">
            <a href="{{url ('plants/create')}}" class="btn btn-success">
                <i class="fa fa-btn fa-plus"></i>
@lang('messages.create')

            </a>
        </div>
            </div>
    </div>
    @endcan
@endif
            <!-- Registered Plants -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.plants')
                    </div>
                    <div class="panel-body">
{!! $dataTable->table() !!}
                </div>
                </div>
        </div>
    </div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
