@if (!isset($index))
<label for="value" class="col-sm-3 control-label mandatory">
@lang('messages.value')
</label>
<div class="col-sm-6">
@endif
<textarea name ='value{{ isset($index) ? "[$index][$traitorder]" : "" }}' id='value{{ isset($index) ? "[$index][$traitorder]" : "" }}'
@if (isset($index) and isset($measurement))
    disabled
@endif
>{{
    isset($index) ?
    old('value.' . $index . '.' . $traitorder, isset($measurement) ? $measurement->valueActual : null) :
    old('value', isset($measurement) ? $measurement->valueActual : null)
}}
</textarea>
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
@endif
