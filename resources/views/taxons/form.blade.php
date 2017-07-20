<!-- name -->
<div class="form-group">
    <label for="name" class="col-sm-3 control-label">
@lang('messages.name')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($taxon) ? $taxon->name : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang ('messages.hint_taxon_name')
    </div>
  </div>
</div>

		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-primary" id="checkih">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.checkapis')
				</button>
				<div class="spinner" id="spinner"> </div>
			    </div>
			</div>

<!-- tax rank -->
<div class="form-group">
    <label for="level" class="col-sm-3 control-label">
@lang('messages.taxon_level')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('level', isset($taxon) ? $taxon->level : null); ?>

	<select name="level" id="level" class="form-control" >
	@foreach (App\Taxon::TaxLevels() as $level)
		<option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
			@lang ('levels.tax.' . $level )
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.taxon_level_hint')
    </div>
  </div>
</div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="valid" {{ old('valid', isset($taxon) ? $taxon->valid : null ) ? 'checked' : '' }}>
@lang('messages.valid')

                                    </label>
                                </div>
                            </div>
                        </div>

