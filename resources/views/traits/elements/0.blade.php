@if (!isset($index))
<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
@endif
<input name ='value{{ isset($index) ? "[$index][]" : "" }}' id='value{{ isset($index) ? "[$index][]" : "" }}' type="text" class="form-control" value="{{old('value', isset($measurement) ? $measurement->valueActual : null)}}">
@if (isset($odbtrait->unit))
<em>@lang('messages.unit'): {{ $odbtrait->unit }} </em><br/>
@endif
@if (isset($odbtrait->range_min) or isset($odbtrait->range_max))
<em>@lang('messages.range'): {{ $odbtrait->rangeDisplay }} </em>
@endif
@if (!isset($index))
</div>
@endif

