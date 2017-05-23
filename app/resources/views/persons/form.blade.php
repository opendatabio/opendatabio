<div class="form-group">
    <label for="full_name" class="col-sm-3 control-label">Full Name</label>
    <div class="col-sm-6">
	<input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name', isset($person) ? $person->full_name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="abbreviation" class="col-sm-3 control-label">Abbreviation</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="abbreviation" id="abbreviation" class="form-control" value="{{ old('abbreviation', isset($person) ? $person->abbreviation : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	The abbreviation field is the abbreviated name which the person uses in publications and other 
	catalogs. It must contain only uppercase letters, hyphens, periods, commas or spaces. A valid abbreviation for
	the name "Charles Robert Darwin" would be "DARWIN, C. R.".
	When registering a new person, the system suggests the name abbreviation, but the user is free to change 
	it to better adapt it to the usual abbreviation used by each person. 
	The abbreviation should be unique for each person.
    </div>
  </div>
</div>
<div class="form-group">
    <label for="email" class="col-sm-3 control-label">E-mail</label>
	    <div class="col-sm-6">
	<input type="text" name="email" id="email" class="form-control" value="{{ old('email', isset($person) ? $person->email : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="institution" class="col-sm-3 control-label">Institution</label>
	    <div class="col-sm-6">
	<input type="text" name="institution" id="institution" class="form-control" value="{{ old('institution', isset($person) ? $person->institution : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="herbarium_id" class="col-sm-3 control-label">Herbarium</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('herbarium_id', isset($person) ? $person->herbarium_id : null); ?>

	<select name="herbarium_id" id="herbarium_id" class="form-control" >
		<option value=0>&nbsp;</option>
	@foreach ($herbaria as $herbarium)
		<option value="{{$herbarium->id}}" {{ $herbarium->id == $selected ? 'selected' : '' }}>{{$herbarium->acronym}}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	Is this person associated with an Herbarium? This is useful to register specialists.
    </div>
  </div>
</div>
