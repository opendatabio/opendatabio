@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-offset-2 col-sm-8">



            <!-- Registered Locations -->
                <div class="panel panel-default">
                    <div class="panel-heading">

                      @lang('messages.registered_locations')
                      @if(isset($object))
                        @lang('messages.for')
                        <strong>
                        {{ class_basename($object) }}
                        </strong>
                        &nbsp;
                        {!! $object->rawLink() !!}
                      @endif
                      &nbsp;&nbsp;
                      <a data-toggle="collapse" href="#help" class="btn btn-default">
                        @lang('messages.help')
                      </a>

                      @can ('create', App\Models\Location::class)
                  				<a href="{{url ('locations/create')}}" class="btn btn-success">
                              @lang('messages.create')
                  				</a>
                      @endcan

                    </div>
                    <div id="help" class="panel-body collapse">
                	     @lang('messages.location_hint')
                       <code>@lang('messages.download_login')</code>
                    </div>

                    @if (Auth::user())
                      {!! View::make('common.exportdata')->with([
                            'object' => isset($object) ? $object : null,
                            'export_what' => 'Location'
                      ]) !!}
                      <!--- delete form -->
                      {!! View::make('common.batchdelete')->with([
                            'url' => url('locations/batch_delete'),
                      ]) !!}
                    @else
                      <br>
                    @endif
                <div class="panel-body">
                  {!! $dataTable->table() !!}
                </div>
                </div>
        </div>
    </div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}


<script>
    const table = $('#dataTableBuilder');
    table.on('preXhr.dt',function(e,settings,data) {
      data.adm_level = $("#location_level option").filter(':selected').val();
      /* an any defined in the export form */
      data.project = $("input[name='project']").val();
      data.location = $("input[name='location_root']").val();
      data.taxon = $("input[name='taxon_root']").val();
      /*console.log(data.level,data.project,data.location,data.taxon); */
    });
    $('#location_level').on('change',function() {
       table.DataTable().ajax.reload();
       return false;
    });
</script>






<script>

$(document).ready(function() {


$('#export_sumbit').on('click',function(e){
  var table =  $('#dataTableBuilder').DataTable();
  var rows_selected = table.column( 0 ).checkboxes.selected();
  $('#export_ids').val( rows_selected.join());
  $("#export_form"). submit();
});


});

</script>
@endpush
