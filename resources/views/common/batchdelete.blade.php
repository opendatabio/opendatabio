<div class="panel-body" style="display: none;" id='delete_pannel'>
  <div  class="col-sm-12" id="delete_hint" ></div>
  <div class="col-sm-12" id='delete_form' style="display: none;">
  <br>
  <form action="{{ $url }}" method="POST" class="form-horizontal" >
      <!-- csrf protection -->
      {{ csrf_field() }}
      <input type='hidden' name='ids_to_delete' id='ids_to_delete' value="" >
      <button type='submit' class="btn btn-danger" id='export_sumbit'>@lang('messages.confirm_deletion')</button>
  </form>
  </div>
</div>
