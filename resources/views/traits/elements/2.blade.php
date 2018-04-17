@if (!isset($index))
<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
@endif
    <?php $selected = isset($index) ? 
        old('value.' . $index . '.' . $traitorder, (isset($measurement) and $measurement->categories) ? $measurement->categories()->first()->category_id : null) :
        old('value', (isset($measurement) and $measurement->categories) ? $measurement->categories()->first()->category_id : null)
; ?>

    <select name='value{{ isset($index) ? "[$index][$traitorder]" : "" }}' id='value{{ isset($index) ? "[$index][$traitorder]" : "" }}' class="form-control" 
@if (isset($index) and isset($measurement)) 
    disabled
@endif

>
        <option value=""></option>
	@foreach ($odbtrait->categories as $cat )
		<option value="{{ $cat->id }}" {{ $cat->id == $selected ? 'selected' : '' }}>
<?php
// this file serves both CATEGORICAL and ORDINAL
if ($odbtrait->type == 4) echo $cat->rank . " - ";
echo $cat->name;
?>
		</option>
	@endforeach
	</select>
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
