<div class="form-group">
    <label for="full_name" class="col-sm-3 control-label mandatory">
@lang('messages.full_name')
</label>
    <div class="col-sm-6">
	<input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name', isset($person) ? $person->full_name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="abbreviation" class="col-sm-3 control-label mandatory">
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
    <label for="biocollection_id" class="col-sm-3 control-label">
@lang('messages.biocollection')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('biocollection_id', isset($person) ? $person->biocollection_id : null); ?>

	<select name="biocollection_id" id="biocollection_id" class="form-control" >
		<option value='' >&nbsp;</option>
	@foreach ($biocollections as $biocollection)
		<option value="{{$biocollection->id}}" {{ $biocollection->id == $selected ? 'selected' : '' }}>{{$biocollection->acronym}}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.person_biocollection_hint')
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
{!! Multiselect::autocomplete('specialist', $taxons->pluck('fullname', 'id'), $person->taxons->pluck('id'), ['class' => 'multiselect form-control']) !!}
            </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.person_specialist_hint')
    </div>
  </div>
</div>
@endif <!-- isset person -->

<!-- notes for person -->
<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($person) ? $person->notes : null) }}</textarea>
            </div>
</div>
