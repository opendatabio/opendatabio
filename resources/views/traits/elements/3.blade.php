@if (isset($index))
<p class="alert alert-danger"><strong>NOTE:</strong> multi selectors are currently not supported in forms</p>
@else
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
@endif
<script type="text/javascript">
// TODO: avoid duplication!!
$(document).ready(function(){
    /** For use in multiple selectors. */
    // Original: http://odyniec.net/articles/multiple-select-fields/
    // The elements are tied by the NAME, CLASS AND ID attributes, use them as
    // span: ID specialist-span
    // select: ID specialist-ms CLASS .multiselect
    // inputs: ID specialists[] NAME specialist[] CLASS .multiselector
    $(".multiselect").change(function()
        {
            var $name = $(this).attr('id');
            $name = $name.substring(0, $name.length-3);
            var $span = $("#" + $name + "-span");
            if ( $(this).val() === "") {
                return;
            }
            if ($span.find('input[value=' + $(this).val() + ']').length == 0) {
                $span.append('<span class="multiselector" onclick="$(this).remove();">' +
                    '<input type="hidden" name="' + $name + '[]" value="' +
                    $(this).val() + '" /> ' +
                    $(this).find('option:selected').text() + '</span>');
            }
        });
});
</script>
