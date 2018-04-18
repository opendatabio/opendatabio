@if (!isset($index))
<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
@endif
<input name ='value{{ isset($index) ? "[$index][$traitorder]" : "" }}' id='value{{ isset($index) ? "[$index][$traitorder]" : "" }}' type="text" class="form-control spectrum" value="{{ 
    isset($index) ? 
    old('value.' . $index . '.' . $traitorder, isset($measurement) ? $measurement->valueActual : null) : 
    old('value', isset($measurement) ? $measurement->valueActual : null)
}}"
@if (isset($index) and isset($measurement)) 
    disabled
@endif
>
@if (isset($index) and isset($measurement)) 
<span style="float:right">
    <a href="{{url('measurements/' . $measurement->id . '/edit')}}" target="_blank">
            @lang('messages.edit')
        <i class="glyphicon glyphicon-new-window"></i>
    </a>
</span>
@endif
@if (!isset($index))
</div>
<script>
// This works in the measurement screen, when this view is appended via jQuery
if (typeof jQuery !== 'undefined') {
$(document).ready(function() {
	$("#value").spectrum({
		flat:true,
		showInput:true,
		showPalette: true,
		showPaletteOnly: true,
		togglePaletteOnly: true,
		togglePaletteMoreText: "@lang('spectrum.more')",
		togglePaletteLessText: "@lang('spectrum.less')",
		preferredFormat: "hex",
        showButtons: false,
		palette: {!! json_encode(config('app.spectrum')) !!}
});
});
}
</script>
@endif
