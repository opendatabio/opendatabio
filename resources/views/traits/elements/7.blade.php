@if (($odbtrait->link_type == "App\Taxon") || ($odbtrait->link_type == "App\Plant") || ($odbtrait->link_type == "App\Location"))
<div class="form-group">
<label for="link" class="col-sm-3 control-label">
@lang('messages.link') 
</label>
<div class="col-sm-6">
    @if ($odbtrait->link_type == "App\Taxon")
    <input type="text" name="taxons_link_autocomplete" id="taxons_link_autocomplete" class="form-control autocomplete"
    value="{{ old('taxons_link_autocomplete', (isset($measurement) and $measurement->linked) ? $measurement->linked->fullname : '') }}">
    @elseif ($odbtrait->link_type == "App\Plant")
    <input type="text" name="plants_link_autocomplete" id="plants_link_autocomplete" class="form-control autocomplete"
    value="{{ old('plants_link_autocomplete', (isset($measurement) and $measurement->linked) ? $measurement->linked->fullname : '') }}">
    @elseif ($odbtrait->link_type == "App\Location")
    <input type="text" name="locations_link_autocomplete" id="locations_link_autocomplete" class="form-control autocomplete"
    value="{{ old('locations_link_autocomplete', (isset($measurement) and $measurement->linked) ? $measurement->linked->fullname : '') }}">
    @endif
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
        $("#taxons_link_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}","#link_id", "@lang('messages.noresults')");
        $("#plants_link_autocomplete").odbAutocomplete("{{url('plants/autocomplete')}}","#link_id", "@lang('messages.noresults')");
        $("#locations_link_autocomplete").odbAutocomplete("{{url('locations/autocomplete')}}","#link_id", "@lang('messages.noresults')");
    });
}
</script>
