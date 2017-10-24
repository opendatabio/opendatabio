<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
<input name ="value" id="value" type="text" class="form-control" value="{{old('value', isset($measurement) ? $measurement->valueActual : null)}}">
@if (isset($odbtrait->unit))
<em>@lang('messages.unit'): {{ $odbtrait->unit }} </em><br/>
@endif
@if (isset($odbtrait->range_min) or isset($odbtrait->range_max))
<em>@lang('messages.range'): {{ $odbtrait->rangeDisplay }} </em>
@endif
</div>

