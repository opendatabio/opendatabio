<div class="form-group">
    <label for="full_name" class="col-sm-3 control-label">
@lang('messages.full_name')
</label>
    <div class="col-sm-6">
	<input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name', isset($person) ? $person->full_name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="abbreviation" class="col-sm-3 control-label">
@lang('messages.abbreviation')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="abbreviation" id="abbreviation" class="form-control" value="{{ old('abbreviation', isset($person) ? $person->abbreviation : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.abbreviation_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="email" class="col-sm-3 control-label">
@lang('messages.email')
</label>
	    <div class="col-sm-6">
	<input type="text" name="email" id="email" class="form-control" value="{{ old('email', isset($person) ? $person->email : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="institution" class="col-sm-3 control-label">
@lang('messages.institution')
</label>
	    <div class="col-sm-6">
	<input type="text" name="institution" id="institution" class="form-control" value="{{ old('institution', isset($person) ? $person->institution : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="herbarium_id" class="col-sm-3 control-label">
@lang('messages.herbarium')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('herbarium_id', isset($person) ? $person->herbarium_id : null); ?>

	<select name="herbarium_id" id="herbarium_id" class="form-control" >
		<option value='' >&nbsp;</option>
	@foreach ($herbaria as $herbarium)
		<option value="{{$herbarium->id}}" {{ $herbarium->id == $selected ? 'selected' : '' }}>{{$herbarium->acronym}}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.person_herbarium_hint')
    </div>
  </div>
</div>

@if (isset($person))
<div class="form-group">
    <label for="specialist" class="col-sm-3 control-label">
@lang('messages.specialist_in')
</label>
        <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
	    <div class="col-sm-6">
<!-- already specialist in -->
<span id = "specialist_ul">
    @foreach ($person->taxons as $taxon)
    <span class="multipleSelector">
  <input type="hidden" name="specialist[]" value="{{ $taxon->id  }}" />
  {{$taxon->name}}
 </span>
    @endforeach
</span>
	<select name="multi-select" id="multi-select" class="form-control">
		<option value='' >&nbsp;</option>
	@foreach ($taxons as $taxon)
		<option value="{{$taxon->id}}" >{{ $taxon->name }}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.person_specialist_hint')
    </div>
  </div>
</div>
@endif <!-- isset person -->
<script src="http://localhost/opendatabio/js/app.js"></script>
<script>
// Thanks to http://odyniec.net/articles/multiple-select-fields/
$(document).ready(function(){
    $("#multi-select").change(function() 
    {
            var $ul = $('#specialist_ul');
            if ( $(this).val() === "") return;
            if ($ul.find('input[value=' + $(this).val() + ']').length == 0)
                    $ul.append('<span class="multipleSelector" onclick="$(this).remove();">' +
                    '<input type="hidden" name="specialist[]" value="' + 
                    $(this).val() + '" /> ' +
                    $(this).find('option:selected').text() + '</span>');
    });
    $(".multipleSelector").click(function() { 
            $(this).remove();
    });

});
</script>
<style>
.multipleSelector {
    display: inline-block;
    border: 1px dashed;
    padding: 2px;
    margin: 5px;
    cursor: pointer;
}
.multipleSelector:after {
  font-family: "Glyphicons Halflings";
  content: "\e014"; /* Code for remove */
  padding-left: 3px;
}

</style>
