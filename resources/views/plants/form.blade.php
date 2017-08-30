<!-- name -->
<div class="form-group">
    <label for="tag" class="col-sm-3 control-label">
@lang('messages.plant_tag')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="tag" id="tag" class="form-control" value="{{ old('tag', isset($plant) ? $plant->tag : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
@lang('messages.hint_plant_tag')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="location_id" class="col-sm-3 control-label">
@lang('messages.location')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('location_id', isset($plant) ? $plant->location_id : null); ?>

	<select name="location_id" id="location_id" class="form-control" >
	@foreach ($locations as $location)
		<option value="{{$location->id}}" {{ $location->id == $selected ? 'selected' : '' }}>
            {{ $location->name }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.plant_location_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="project" class="col-sm-3 control-label">
@lang('messages.project')
</label>
        <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('project_id', isset($plant) ? $plant->project_id : null); ?>

	<select name="project_id" id="project_id" class="form-control" >
	@foreach ($projects as $project)
		<option value="{{$project->id}}" {{ $project->id == $selected ? 'selected' : '' }}>
            {{ $project->name }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.plant_project_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="date" class="col-sm-3 control-label">
@lang('messages.collection_date')
</label>
        <a data-toggle="collapse" href="#hint4" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<select name="date_year" id="date_year" class="form-control partdatepicker">
	<?php $selected = old('date_year', isset($plant) ? $plant->year : null); ?>
	@for ($i = config('app.max_year'); $i >= config('app.min_year'); $i --)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{$i}}
        </option>
	@endfor
	</select> /
	<select name="date_month" id="date_month" class="form-control partdatepicker">
	<?php $selected = old('date_month', isset($plant) ? $plant->month : null); ?>
        <option value=0 > 
            @lang('messages.unknown_date')
        </option>
	@for ($i = 1; $i <= 12; $i ++)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{str_pad($i, 2, '0',STR_PAD_LEFT)}}
        </option>
	@endfor
	</select> / 
	<select name="date_day" id="date_day" class="form-control partdatepicker">
	<?php $selected = old('date_day', isset($plant) ? $plant->day : null); ?>
        <option value=0 > 
            @lang('messages.unknown_date')
        </option>
	@for ($i = 1; $i <= 31; $i ++)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{str_pad($i, 2, '0',STR_PAD_LEFT)}}
        </option>
	@endfor
	</select> 
            </div>
  <div class="col-sm-12">
    <div id="hint4" class="panel-collapse collapse">
	@lang('messages.plant_date_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($plant) ? $plant->notes : null) }}</textarea>
            </div>
</div>

<div class="form-group">
    <label for="relative_position" class="col-sm-3 control-label">
@lang('messages.relative_position')
</label>
        <a data-toggle="collapse" href="#hint12" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	X: <input type="text" name="x" id="x" class="form-control latlongpicker" value="{{ old('x', isset($location) ? $location->x : null) }}">(m)&nbsp;
	Y: <input type="text" name="y" id="y" class="form-control latlongpicker" value="{{ old('y', isset($location) ? $location->y : null) }}">(m)
            </div>
  <div class="col-sm-12">
    <div id="hint12" class="panel-collapse collapse">
	@lang('messages.plant_position_hint')
    </div>
  </div>
</div>

<!-- collector -->
<div class="form-group">
    <label for="collectors" class="col-sm-3 control-label">
@lang('messages.collectors')
</label>
        <a data-toggle="collapse" href="#hint5" class="btn btn-default">?</a>
	    <div class="col-sm-6">
<span id = "collector-ul">
    @if (is_null(old('collector'))) <!-- get the data from the database -->
        @if (isset($plant))
    @foreach ($plant->collectors as $collector)
    <span class="multipleSelector">
  <input type="hidden" name="collector[]" value="{{ $collector->person->id  }}" />
  {{$collector->person->abbreviation}}
 </span>
     @endforeach
        @endif
     @else <!-- !isnull old, so we get the data from old() -->
    @foreach (old('collector') as $collector)
    <span class="multipleSelector">
  <input type="hidden" name="collector[]" value="{{ $collector  }}" />
  {{ $persons->find($collector)->abbreviation }}
 </span>
     @endforeach
     @endif
</span>
	<select name="collector-ms" id="collector-ms" class="form-control multi-select">
		<option value='' >&nbsp;</option>
	@foreach ($persons as $person)
		<option value="{{$person->id}}" >{{ $person->abbreviation }}</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint5" class="panel-collapse collapse">
	@lang('messages.plant_collectors_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="taxon_id" class="col-sm-3 control-label">
@lang('messages.taxon')
</label>
        <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="taxon_autocomplete" id="taxon_autocomplete" class="form-control"
    value="{{ old('taxon_autocomplete', (isset($plant) and $plant->identification) ? $plant->identification->taxon->fullname : null) }}">
    <input type="hidden" name="taxon_id" id="taxon_id"
    value="{{ old('taxon_id', (isset($plant) and $plant->identification) ? $plant->identification->taxon_id : null) }}">


            </div>
  <div class="col-sm-12">
    <div id="hint6" class="panel-collapse collapse">
	@lang('messages.plant_taxon_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="modifier" class="col-sm-3 control-label">
@lang('messages.modifier')
</label>
        <a data-toggle="collapse" href="#hint9" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('modifier', (isset($plant) and $plant->identification) ? $plant->identification->modifier : null); ?>
    
@foreach (App\Identification::MODIFIERS as $modifier)
        <span>
    		<input type = "radio" name="modifier" value="{{$modifier}}" {{ $modifier == $selected ? 'checked' : '' }}>
            @lang('levels.identification.' . $modifier)
		</span>
	@endforeach
            </div>
  <div class="col-sm-12">
    <div id="hint9" class="panel-collapse collapse">
	@lang('messages.plant_modifier_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="identifier_id" class="col-sm-3 control-label">
@lang('messages.identifier')
</label>
        <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('identifier_id', (isset($plant) and $plant->identification) ? $plant->identification->person_id : null); ?>

	<select name="identifier_id" id="identifier_id" class="form-control" >
		<option value='' >&nbsp;</option>

	@foreach ($persons as $identifier)
		<option value="{{$identifier->id}}" {{ $identifier->id == $selected ? 'selected' : '' }}>
            {{ $identifier->abbreviation }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint7" class="panel-collapse collapse">
	@lang('messages.plant_identifier_id_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="identification_date" class="col-sm-3 control-label">
@lang('messages.identification_date')
</label>
        <a data-toggle="collapse" href="#hint8" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<select name="identification_date_year" id="identification_date_year" class="form-control partdatepicker">
	<?php $selected = old('identification_date_year', (isset($plant) and $plant->identification) ? $plant->identification->year : null); ?>
	@for ($i = config('app.max_year'); $i >= config('app.min_year'); $i --)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{$i}}
        </option>
	@endfor
	</select> /
	<select name="identification_date_month" id="identification_date_month" class="form-control partdatepicker">
	<?php $selected = old('identification_date_month', (isset($plant) and $plant->identification) ? $plant->identification->month : null); ?>
        <option value=0 > 
            @lang('messages.unknown_date')
        </option>
	@for ($i = 1; $i <= 12; $i ++)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{str_pad($i, 2, '0',STR_PAD_LEFT)}}
        </option>
	@endfor
	</select> / 
	<select name="identification_date_day" id="identification_date_day" class="form-control partdatepicker">
	<?php $selected = old('identification_date_day', (isset($plant) and $plant->identification) ? $plant->identification->day : null); ?>
        <option value=0 > 
            @lang('messages.unknown_date')
        </option>
	@for ($i = 1; $i <= 31; $i ++)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{str_pad($i, 2, '0',STR_PAD_LEFT)}}
        </option>
	@endfor
	</select> 
            </div>
  <div class="col-sm-12">
    <div id="hint8" class="panel-collapse collapse">
	@lang('messages.plant_identification_date_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="herbarium_id" class="col-sm-3 control-label">
@lang('messages.herbarium')
</label>
        <a data-toggle="collapse" href="#hint10" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('herbarium_id', (isset($plant) and $plant->identification) ? $plant->identification->herbarium_id : null); ?>

	<select name="herbarium_id" id="herbarium_id" class="form-control" >
		<option value='' >&nbsp;</option>

	@foreach ($herbaria as $herbarium)
		<option value="{{$herbarium->id}}" {{ $herbarium->id == $selected ? 'selected' : '' }}>
            {{ $herbarium->acronym }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint10" class="panel-collapse collapse">
	@lang('messages.plant_herbarium_id_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="identification_notes" class="col-sm-3 control-label">
@lang('messages.identification_notes')
</label>
	    <div class="col-sm-6">
	<textarea name="identification_notes" id="identification_notes" class="form-control">{{ old('identification_notes', (isset($plant) and $plant->identification) ? $plant->identification->notes : null) }}</textarea>
            </div>
</div>
