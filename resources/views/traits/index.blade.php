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
@lang('messages.traits_hint')
      </div>
    </div>
  </div>

@can ('create', App\ODBTrait::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.create_trait')
                </div>

                <div class="panel-body">
                <a href="{{url('traits/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

            <!-- Registered traits -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.traits')
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
