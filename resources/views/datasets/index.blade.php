@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-offset-2 col-sm-8">

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

                        &nbsp;&nbsp;
                        <a data-toggle="collapse" href="#data_hint" class="btn btn-default">
                          @lang('messages.help')
                        </a>

                        @if ($mydatasets)
                          &nbsp;&nbsp;
                          <a data-toggle="collapse" href="#mydatasets" class="btn btn-default">@lang('messages.my_datasets')</a>
                        @endif

                        @can ('create', App\Models\Dataset::class)
                        &nbsp;&nbsp;
                        <a href="{{url('datasets/create')}}" class="btn btn-success">
                          @lang ('messages.create')
                        </a>
                        @endcan


                    </div>

                    <div id="data_hint" class="panel-body collapse">
                      @lang('messages.dataset_hint')
                    </div>

                    @if ($mydatasets)
                    <div id="mydatasets" class="panel-body collapse">
                          <br>
                          <ul>
                            @foreach ($mydatasets as $dataset)
                              <li>{!! $dataset->rawLink() !!}
                                (@lang('levels.project.' . $dataset->pivot->access_level ))
                              </li>
                            @endforeach
                          </ul>
                    </div>
                    @endif
                    <br>
                    <div class="panel-body">
                        {!! $dataTable->table([],true) !!}
                    </div>
                </div>
        </div>
    </div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function() {

  var table = $('#dataTableBuilder').DataTable();

  $('tbody').on('click', 'tr',function () {
      //console.log( table.row( this ).data() );
      var id =  table.row( this ).data().id;
      var id = 'description_'+id;
      if($('#'+id).is(':hidden')) {
        $('#'+id).show();
      } else {
        $('#'+id).hide();
      }
  });


});


</script>

@endpush
