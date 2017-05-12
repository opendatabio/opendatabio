<div class="form-group">
    <label for="full_name" class="col-sm-3 control-label">Full Name</label>
    <div class="col-sm-6">
	<input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name', isset($person) ? $person->full_name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="abbreviation" class="col-sm-3 control-label">Abbreviation</label>
	    <div class="col-sm-6">
	<input type="text" name="abbreviation" id="abbreviation" class="form-control" value="{{ old('abbreviation', isset($person) ? $person->abbreviation : null) }}">
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
