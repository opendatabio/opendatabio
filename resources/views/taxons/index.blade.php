@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-offset-2 col-sm-8">





        <!-- Registered Taxons -->
        <div class="panel panel-default">
            <div class="panel-heading">
              @if (isset($object))
                  @lang('messages.taxon_list')
                  <strong>
                    {{ class_basename($object )}}
                  </strong>
                  {!! $object->rawLink(true) !!}
                  @if (isset($object_second))
                  &nbsp;>>&nbsp;<strong>{{class_basename($object_second )}}</strong> {!! $object_second->rawLink(true) !!}
                  @endif
                  &nbsp;&nbsp;
                  <a href="#" id="about_list" class="btn btn-default"><i class="fas fa-question"></i></a>
                  <div id='about_list_text' ></div>
             @else
                  @lang('messages.registered_taxons')
                  &nbsp;&nbsp;
                  <a data-toggle="collapse" href="#help" class="btn btn-default">
                    @lang('messages.help')
                  </a>
             @endif
             @can ('create', App\Models\Taxon::class)
               &nbsp;&nbsp;
               <a href="{{url ('taxons/create')}}" class="btn btn-success">
                 @lang('messages.create')
               </a>
             @endcan
           </div>
           <div id="help" class="panel-body collapse">
               @lang('messages.taxon_index_hint')
               <code>@lang('messages.download_login')</code>
           </div>




           @if (Auth::user())
            {!! View::make('common.exportdata')->with([
                  'object' => isset($object) ? $object : null,
                  'object_second' => isset($object_second) ? $object_second : null,
                  'export_what' => 'Taxon'
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
      data.level = $("#taxon_level option").filter(':selected').val();
      data.project = $("input[name='project']").val();
      data.location = $("input[name='location_root']").val();
      data.taxon = $("input[name='taxon_root']").val();
      //console.log(data.level,data.project,data.location,data.taxon);
    });
    $('#taxon_level').on('change',function() {
       table.DataTable().ajax.reload();
       return false;
    });
</script>



<script>
$(document).ready(function() {

$("#about_list").on('click',function(){
  if ($('#about_list_text').is(':empty')){
    var records = $('#dataTableBuilder').DataTable().ajax.json().recordsTotal;
    if (records == 0) {
      $('#about_list_text').html("<br>@lang('messages.no_permission_list')<br>");
    } else {
      $('#about_list_text').html("<br>@lang('messages.taxon_identification_index')<br>");
    }
  } else {
    $('#about_list_text').html(null);
  }
});


$('#export_sumbit').on('click',function(e){
  var table =  $('#dataTableBuilder').DataTable();
  var rows_selected = table.column( 0 ).checkboxes.selected();
  $('#export_ids').val( rows_selected.join());
  $("#export_form"). submit();
});



});

</script>
@endpush
