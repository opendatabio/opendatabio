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

});

</script>
@endpush
