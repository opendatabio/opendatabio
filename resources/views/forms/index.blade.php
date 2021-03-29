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
@lang('messages.forms_hint')
      </div>
    </div>
  </div>

@can ('create', App\Models\Form::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.create_form')
                </div>

                <div class="panel-body">
                <a href="{{url('forms/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

            <!-- Registered forms -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.forms')
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
