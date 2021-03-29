@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-offset-2 col-sm-8">

@can ('create', App\Models\Voucher::class)
      <div class="panel panel-default">
        <div class="panel-heading">
          @lang('messages.create_voucher')
        </div>
        <div class="panel-body">
			    <div class="col-sm-6">
            @if (isset($object))
              <a href="{{url ( $object->typename . '/' . $object->id .   '/vouchers/create')}}" class="btn btn-success">
                <i class="fa fa-btn fa-plus"></i>
                @lang('messages.create')
              </a>
            @else
              <a href="{{ url('vouchers/create') }}" class="btn btn-success">
                <i class="fa fa-btn fa-plus"></i>
                @lang('messages.create')
              </a>
            @endif
          </div>
        </div>
	    </div>
@endcan

            <!-- Registered Vouchers -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @if (isset($object)) <!-- we're inside a Location, Project or Taxon view -->
                            @lang('messages.voucher_list_for')<strong> {{ class_basename($object) }}</strong>
                            {!! $object->rawLink() !!}
                        @else
                          @lang('messages.vouchers')
                          &nbsp;&nbsp;
                          <a data-toggle="collapse" href="#help" class="btn btn-default">
                          @lang('messages.help')
                          </a>
                        @endif
                        @if (isset($object_second))
                        &nbsp;>>&nbsp;<strong>{{class_basename($object_second )}}</strong> {!! $object_second->rawLink(true) !!}
                        @endif
                    </div>
                    <div id="help" class="panel-body collapse">
                      @lang('messages.vouchers_hint')
                      <code>@lang('messages.download_login')</code>
                    </div>
                    @if (Auth::user())
                    {!! View::make('common.exportdata')->with([
                          'object' => isset($object) ? $object : null,
                          'object_second' => isset($object_second) ? $object_second : null,
                          'export_what' => 'Voucher'
                    ]) !!}
                    @else
                      <br>
                    @endif
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
