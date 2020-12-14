@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-1 col-sm-10">


@if(!isset($object))  <!-- then not specific scope is requested -->
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
    	@lang('messages.taxon_index_hint')
    </div>
  </div>
</div>
@endif

@can ('create', App\Taxon::class)
<div class="panel panel-default">
  <div class="panel-heading">
      @lang('messages.create_taxon')
  </div>
  <div class="panel-body">
    <div class="col-sm-6">
		    <a href="{{url ('taxons/create')}}" class="btn btn-success">
				 <i class="fa fa-btn fa-plus"></i>
          @lang('messages.create')
        </a>
		</div>
  </div>
 </div>
@endcan




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
             @endif
           </div>

           @if (Auth::user())
            {!! View::make('common.exportdata')->with([
                  'object' => isset($object) ? $object : null,
                  'object_second' => isset($object_second) ? $object_second : null,
                  'export_what' => 'Taxon'
            ]) !!}
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

<script type="text/javascript">
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


$('#exports').on('click', function(e){
    if ($('#export_pannel').is(":visible")) {
      $('#export_pannel').hide();
    } else {
      $('#export_pannel').show();
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
