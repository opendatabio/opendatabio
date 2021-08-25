@extends('layouts.app')
@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">
      <?php
        if (isset($dataset)) {
          $is_open = in_array($dataset->privacy,[App\Models\Dataset::PRIVACY_REGISTERED,App\Models\Dataset::PRIVACY_PUBLIC]);
        } else {
          $is_open = (null != Auth::user()) ? true : false;
        }
       ?>
      @if($is_open)
      <div class="panel-heading">
        @lang('messages.individuallocations') @lang('messages.for')
          @lang('messages.dataset'): <strong>{{ $dataset->name}}</strong>
      </div>
      <div class="panel-body">
      @if (Auth::user())
        {!! View::make('common.exportdata')->with([
              'object' => isset($dataset) ? $dataset : null,
              'object_second' => null,
              'export_what' => 'individual-location'
        ]) !!}
        <br>
      @endif
      {!! $dataTable->table([],true) !!}
      </div>
      @endif
      </div>
    </div>
</div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}

<script >
// Handle form submission event
$('#export_sumbit').on('click',function(e){
  var table =  $('#dataTableBuilder').DataTable();
  var rows_selected = table.column( 0 ).checkboxes.selected();
  $('#export_ids').val( rows_selected.join());
  $("#export_form"). submit();
});
</script>

@endpush
