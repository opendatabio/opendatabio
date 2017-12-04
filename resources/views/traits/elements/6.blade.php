<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
<input name ="value" id="value" type="text" class="form-control" value="{{old('value', isset($measurement) ? $measurement->valueActual : null)}}">
</div>

<script>
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
		palette: {!! json_encode(config('app.spectrum')) !!}
});
});
}
</script>
