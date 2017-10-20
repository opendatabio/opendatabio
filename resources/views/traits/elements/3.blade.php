<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
{!! Multiselect::select('value', 
        $odbtrait->categories->pluck('name', 'id'), 
        isset($measurement) ? $measurement->categories->pluck('category_id') : [], 
        ['class' => 'multiselect form-control']) 
!!}
</div>
