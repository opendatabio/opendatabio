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
@lang('messages.datasets_hint')
      </div>
    </div>
  </div>

@can ('create', App\Dataset::class)
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.create_dataset')
                </div>

                <div class="panel-body">
                <a href="{{url('datasets/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

@if ($mydatasets)
            <div class="panel panel-default">
                <div class="panel-heading">
      <a data-toggle="collapse" href="#mydatasets" class="btn btn-default">@lang('messages.my_datasets')</a>
                </div>
                <div class="panel-collapse collapse" id='mydatasets'>
                  <br>
<ul>
@foreach ($mydatasets as $dataset)
    <li>{!! $dataset->rawLink() !!}
(@lang('levels.project.' . $dataset->pivot->access_level )
)</li>
@endforeach
    </ul>
                </div>
            </div>
@endif


            <!-- Registered Datasets -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.datasets')
                        @if(isset($object))
                          @lang('messages.for')
                          <strong>
                          {{ class_basename($object) }}
                          </strong>
                          {!! $object->rawLink() !!}
                        @endif
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
