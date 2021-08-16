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
@lang('messages.traits_hint')
      </div>
    </div>
  </div>

@can ('create', App\Models\ODBTrait::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.create_trait')
                </div>

                <div class="panel-body">
                <a href="{{url('traits/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

            <!-- Registered traits -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.traits')
                    </div>
                    @if (Auth::user())
                      {!! View::make('common.exportdata')->with([
                            'object' => isset($object) ? $object : null,
                            'export_what' => 'trait'
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
      data.type = $("#trait_type option").filter(':selected').val();
      /* an any defined in the export form */
      //data.project = $("input[name='project']").val();
      //data.location = $("input[name='location_root']").val();
      //data.taxon = $("input[name='taxon_root']").val();
      /*console.log(data.level,data.project,data.location,data.taxon); */
    });
    $('#trait_type').on('change',function() {
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
