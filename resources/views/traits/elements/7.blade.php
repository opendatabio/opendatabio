@if ($odbtrait->link_type == "App\Taxon" || $odbtrait->link_type == "App\Person" || $odbtrait->link_type=="App\Plant") <!-- other link types need separate elements!! -->
    @if (!isset ($index))
        <div class="form-group">
        <label for="link" class="col-sm-3 control-label mandatory">
        @lang('messages.link')
        </label>
        <div class="col-sm-6">
    @endif
            <input type="text" name='link_autocomplete{{ isset($index) ? "[$index][$traitorder]" : "" }}' id='link_autocomplete{{ isset($index) ? "[$index][$traitorder]" : "" }}' class="form-control autocomplete"
            value="{{
            isset($index) ?
                old('link_autocomplete.' . $index . '.' . $traitorder, (isset($measurement) and $measurement->linked) ? $measurement->linked->fullname : '') :
                old('link_autocomplete', (isset($measurement) and $measurement->linked) ? $measurement->linked->fullname : '')
}}"
@if (isset($index) and isset($measurement))
    disabled
@endif
>
            <input type="hidden" name='link_id{{ isset($index) ? "[$index][$traitorder]" : "" }}' id='link_id{{ isset($index) ? "[$index][$traitorder]" : "" }}'
            value="{{
    isset($index) ?
old('link_id.' . $index . '.' . $traitorder, isset($measurement) ? $measurement->value_i : null) :
old('link_id', isset($measurement) ? $measurement->value_i : null)
}}"

@if (isset($index) and isset($measurement))
    disabled
@endif
>
    @if (!isset($index))
        </div>
        </div>
    @endif
@endif


    @if (!isset($index))
<div class="form-group">
<label for="value" class="col-sm-3 control-label">
@lang('messages.value') (@lang('messages.optional'))
</label>
<div class="col-sm-6">
    @endif
<input name ='value{{ isset($index) ? "[$index][$traitorder]" : "" }}' id='value{{ isset($index) ? "[$index][$traitorder]" : "" }}' type="text" class="form-control" value="{{
    isset($index) ?
    old('value.' . $index . '.' . $traitorder, isset($measurement) ? $measurement->value : null) :
    old('value', isset($measurement) ? $measurement->value : null)
}}"
@if (isset($index) and isset($measurement))
    disabled
@endif
>
    @if (!isset($index) && $odbtrait->link_type == "App\Taxon")
</div>
</div>
<script>
// NOTICE: this will only work if called via AJAX. Set up an alternative for direct loading
if (typeof jQuery !== 'undefined') {
    $(document).ready(function(){
      $("#link_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}","#link_id", "@lang('messages.noresults')");
    });
}
</script>
    @endif

    @if (!isset($index) && $odbtrait->link_type == "App\Person")
</div>
</div>
<script>
// NOTICE: this will only work if called via AJAX. Set up an alternative for direct loading
if (typeof jQuery !== 'undefined') {
    $(document).ready(function(){
      $("#link_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#link_id", "@lang('messages.noresults')");
    });
}
</script>
    @endif

    @if (!isset($index) && $odbtrait->link_type == "App\Plant")
  </div>
  </div>
  <script>
  // NOTICE: this will only work if called via AJAX. Set up an alternative for direct loading
  if (typeof jQuery !== 'undefined') {
    $(document).ready(function(){
      $("#link_autocomplete").odbAutocomplete("{{url('plants/autocomplete')}}","#link_id", "@lang('messages.noresults')");
    });
  }
  </script>
    @endif

@if (isset($index) and isset($measurement))
<span style="float:right">
    <a href="{{url('measurements/' . $measurement->id . '/edit')}}" target="_blank">
            @lang('messages.edit')
        <i class="glyphicon glyphicon-new-window"></i>
    </a>
</span>
@endif
