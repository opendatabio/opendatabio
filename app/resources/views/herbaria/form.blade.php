
<div class="form-group">
    <label for="acronym" class="col-sm-3 control-label">Acronym</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="acronym" id="acronym" class="form-control" value="{{ old('acronym') }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	This is the acronym used in the Index Herbariorium, which consists of two to six letters. The other fields will be
	filled in automatically.
    </div>
  </div>
</div>
<div class="form-group">
    <label for="name" class="col-sm-3 control-label">Institution Name</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" readonly>
    </div>
</div>
<div class="form-group">
    <label for="irn" class="col-sm-3 control-label">IRN</label>
	    <div class="col-sm-6">
	<input type="text" name="irn" id="irn" class="form-control" value="{{ old('irn') }}" readonly>
    </div>
</div>
