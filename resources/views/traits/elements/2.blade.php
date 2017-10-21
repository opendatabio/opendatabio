<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
	<?php $selected = old('value', (isset($measurement) and $measurement->categories) ? $measurement->categories()->first()->category_id : null); ?>

	<select name="value" id="value" class="form-control" >
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
</div>
