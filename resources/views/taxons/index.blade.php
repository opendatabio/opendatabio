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
@else <!-- seen taxon for projects or datasets -->
<div class="panel panel-default">
  <div class="panel-heading">
    @lang('messages.taxon_list')
  </div>
  <div class="panel-body">
    <div class="col-sm-12">
      <p>
        <strong>
        {{ str_replace("App\\","",get_class($object)) }}
        </strong>
        &nbsp;
        {!! $object->rawLink() !!}
        &nbsp;&nbsp;
        <a href="#" id='about_list' class="btn btn-default">?</a>
      </p>
    </div>
    <div id='about_list_text' class="col-sm-12"></div>
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
                      @lang('messages.registered_taxons')
                    </div>
                    <div class="panel-body">
                      <div class="col-sm-2">
                        <button class="btn btn-primary" id='exports'>
                          @lang('messages.export_button')
                        </button>
                      </div>
                      <!--- EXPORT FORM hidden -->
                      <div class="col-sm-8" id='export_pannel' hidden>
                        <form action="{{ url('exportdata')}}" method="POST" class="form-horizontal" id='export_form'>
                        <!-- csrf protection -->
                          {{ csrf_field() }}
                        <!--- field to fill with ids to export --->
                        <input type='hidden' name='export_ids' id='export_ids' value='' >
                        <input type='hidden' name='object_type' value='Taxon' >
                          <input type="radio" name="filetype" checked value="csv" >&nbsp;<label>CSV</label>
                          &nbsp;&nbsp;
                          <input type="radio" name="filetype" value="ods">&nbsp;<label>ODS</label>
                          &nbsp;&nbsp;
                          <input type="radio" name="filetype" value="xlsx">&nbsp;<label>XLSX</label>
                          &nbsp;&nbsp;
                          &nbsp;&nbsp;
                          <input type="radio" name="fields" value="all">&nbsp;<label>all</label>
                          &nbsp;&nbsp;
                          <input type="radio" name="fields" checked value="simple">&nbsp;<label>simple</label>
                          &nbsp;&nbsp;
                          <a data-toggle="collapse" href="#hint_exports" class="btn btn-default">?</a>
                          <button type='button' class="btn btn-default" id='export_sumbit'>@lang('messages.submit')</button>
                      </form>
                    </div>
                    <div class="col-sm-12" id='hint_exports' hidden>
                      <br>
                      @lang('messages.export_hint')
                    </div>
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

<script type="text/javascript">
$(document).ready(function() {

$("#about_list").on('click',function(){
  if ($('#about_list_text').is(':empty')){
    var records = $('#dataTableBuilder').DataTable().ajax.json().recordsTotal;
    if (records == 0) {
      $('#about_list_text').html("@lang('messages.no_permission_list')");
    } else {
      $('#about_list_text').html("@lang('messages.taxon_identification_index')");
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
  if (rows_selected.length==0) {
    alert('No rows selected, all records accessible by you will be exported');
  }
  $("#export_form"). submit();
});



});

</script>
@endpush
