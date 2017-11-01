@if ($odbtrait->link_type == "App\Taxon")
<div class="form-group">
<label for="link" class="col-sm-3 control-label">
@lang('messages.link') 
</label>
<div class="col-sm-6">
    <input type="text" name="link_autocomplete" id="link_autocomplete" class="form-control autocomplete"
    value="{{ old('link_autocomplete', (isset($measurement) and $measurement->linked) ? $measurement->linked->fullname : '') }}">
    <input type="hidden" name="link_id" id="link_id"
    value="{{ old('link_id', isset($measurement) ? $measurement->value_i : null) }}">

</div>
</div>
@endif
<div class="form-group">
<label for="value" class="col-sm-3 control-label">
@lang('messages.value') (@lang('messages.optional'))
</label>
<div class="col-sm-6">
<input name ="value" id="value" type="text" class="form-control" value="{{old('value', isset($measurement) ? $measurement->value : null)}}">
</div>
</div>
<script>
// NOTICE: this will only work if called via AJAX. Set up an alternative for direct loading
if (typeof jQuery !== 'undefined') {
    $(document).ready(function(){
        $("#link_autocomplete").devbridgeAutocomplete({
        serviceUrl: "{{url('taxons/autocomplete')}}",
            onSelect: function (suggestion) {
                $("#link_id").val(suggestion.data);
            },
            onInvalidateSelection: function() {
                $("#link_id").val(null);
            }
    });
    });
}
</script>
