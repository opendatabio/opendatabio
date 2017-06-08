
<div class="form-group">
    <label for="acronym" class="col-sm-3 control-label">
@lang('messages.acronym')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="acronym" id="acronym" class="form-control" value="{{ old('acronym') }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang ('messages.hint_herbaria')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="name" class="col-sm-3 control-label">
@lang('messages.institution')
</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" readonly>
    </div>
</div>
<div class="form-group">
    <label for="irn" class="col-sm-3 control-label">
@lang('messages.irn')
</label>
	    <div class="col-sm-6">
	<input type="text" name="irn" id="irn" class="form-control" value="{{ old('irn') }}" readonly>
    </div>
</div>
