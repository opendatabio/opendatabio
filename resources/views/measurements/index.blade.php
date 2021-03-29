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
@lang('messages.measurements_hint')
      </div>
    </div>
  </div>



@if (isset($object->typename))
@can ('create', App\Models\Measurement::class)
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.create_measurement')
                </div>

                <div class="panel-body">
			    <div class="col-sm-6">
				<a href="{{url ( $object->typename . '/' . $object->id .   '/measurements/create')}}" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create')
				</a>
			</div>
                </div>
	    </div>
@endcan
@endif
            <!-- Registered Vouchers -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                      <p>
                      @if (isset($object))
                        @lang('messages.measurements_for')
                        <strong>
                          {{ class_basename($object )}}
                        </strong>
                        {!! $object->rawLink(true) !!}
                      @else
                        <strong>
                        @lang('messages.measurements')
                        </strong>
                      @endif
                      @if (isset($object_second))
                      &nbsp;>>&nbsp;
                      <strong>{{class_basename($object_second )}}</strong>
                      {!! $object_second->rawLink(true) !!}
                      @endif
                      @if (isset($measured_type))
                      &nbsp;>>&nbsp;
                      <strong>{{$measured_type}}</strong>
                      @endif
                      </p>
                    </div>
                    @if (Auth::user())
                    {!! View::make('common.exportdata')->with([
                          'object' => isset($object) ? $object : null,
                          'object_second' => isset($object_second) ? $object_second : null,
                          'measured_type' => isset($measured_type) ? $measured_type : null,
                          'export_what' => 'Measurement'
                    ]) !!}
                    @endif

  <div class="panel-body">
    {!! $dataTable->table() !!}
  </div>
</div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}



<script>

$(document).ready(function() {

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
