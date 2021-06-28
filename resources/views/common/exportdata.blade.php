<div class="panel-body" style="display: none;" id='export_pannel'>
  <!--- EXPORT FORM hidden -->
  <div class="col-sm-12" >
    <form action="{{ url('exportdata')}}" method="POST" class="form-horizontal" id='export_form'>
    <!-- csrf protection -->
      {{ csrf_field() }}
    <!--- field to fill with ids to export --->
    @if (isset($object) and class_basename($object) == "ODBTrait")
      <input type='hidden' name='trait' value='{{ $object->id}}' >
    @elseif (isset($object_second) and class_basename($object_second) == "ODBTrait")
      <input type='hidden' name='trait' value='{{ $object_second->id}}' >
    @endif

    @if (isset($object) and class_basename($object) == "Dataset")
      <input type='hidden' name='dataset' value='{{ $object->id}}' >
    @elseif (isset($object_second) and class_basename($object_second) == "Dataset")
      <input type='hidden' name='dataset' value='{{ $object_second->id}}' >
    @endif

    @if (isset($measured_type))
      <input type='hidden' name='measured_type' value='{{ $measured_type}}' >
    @endif

    @if (isset($object) and class_basename($object) == "Project")
      <input type='hidden' name='project' value='{{ $object->id}}' >
    @elseif (isset($object_second) and class_basename($object_second) == "Project")
      <input type='hidden' name='project' value='{{ $object_second->id}}' >
    @endif


    @if (isset($object) and class_basename($object) == "Taxon")
      Taxon:<input type='hidden' name='taxon_root' value='{{ $object->id}}' >
    @endif
    @if (isset($object) and class_basename($object) == "Location")
      <input type='hidden' name='location_root' value='{{ $object->id}}' >
    @endif
    @if (isset($object) and class_basename($object) == "Individual")
      <input type='hidden' name='individual' value='{{ $object->id}}' >
    @endif
    @if (isset($object) and class_basename($object) == "Voucher")
      <input type='hidden' name='voucher' value='{{ $object->id}}' >
    @endif
    <input type='hidden' name='export_ids' id='export_ids' value='' >
    <input type='hidden' name='object_type' value='{{$export_what}}' >
      <input type="radio" name="filetype" checked value="csv" >&nbsp;<label>CSV</label>
      &nbsp;&nbsp;
      <input type="radio" name="filetype" value="ods">&nbsp;<label>ODS</label>
      &nbsp;&nbsp;
      <input type="radio" name="filetype" value="xlsx">&nbsp;<label>XLSX</label>
      &nbsp;&nbsp;
      &nbsp;&nbsp;
      <input type="radio" name="fields" value="all">&nbsp;<label>all fields</label>
      &nbsp;&nbsp;
      <input type="radio" name="fields" checked value="simple">&nbsp;<label>simple fields</label>
      &nbsp;&nbsp;
      <a data-toggle="collapse" href="#hint_exports" class='btn btn-default'><i class="fas fa-question"></i></a>
      <button type='button' class="btn btn-success" id='export_sumbit'>@lang('messages.submit')</button>
  </form>
</div>
<div class="col-sm-12" id='hint_exports' hidden>
  <br>
  @lang('messages.export_hint')
</div>
</div>
