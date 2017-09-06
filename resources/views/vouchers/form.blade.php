<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label">
@lang('messages.main_collector')
</label>
        <a data-toggle="collapse" href="#hintp" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('person_id', isset($voucher) ? $voucher->person_id : null); ?>

	<select name="person_id" id="person_id" class="form-control" >
	@foreach ($persons as $person)
		<option value="{{$person->id}}" {{ $person->id == $selected ? 'selected' : '' }}>
            {{ $person->abbreviation }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hintp" class="panel-collapse collapse">
	@lang('messages.voucher_person_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="number" class="col-sm-3 control-label">
@lang('messages.voucher_number')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="number" id="number" class="form-control" value="{{ old('number', isset($voucher) ? $voucher->number : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
@lang('messages.hint_voucher_number')
    </div>
  </div>
</div>

<!-- collectors -->
<div class="form-group">
    <label for="collectors" class="col-sm-3 control-label">
@lang('messages.additional_collectors')
</label>
        <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! Multiselect::select(
    'collector', 
    $persons->pluck('abbreviation', 'id'), 
    isset($voucher) ? $voucher->collectors->pluck('person_id') : [],
    ['class' => 'multiselect form-control']
) !!}
            </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.voucher_collectors_hint')
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
	<?php $selected = old('date_year', isset($voucher) ? $voucher->year : null); ?>
	@for ($i = config('app.max_year'); $i >= config('app.min_year'); $i --)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{$i}}
        </option>
	@endfor
	</select> /
	<select name="date_month" id="date_month" class="form-control partdatepicker">
	<?php $selected = old('date_month', isset($voucher) ? $voucher->month : null); ?>
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
	<?php $selected = old('date_day', isset($voucher) ? $voucher->day : null); ?>
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
	@lang('messages.voucher_date_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="parent_type" class="col-sm-3 control-label">
@lang('messages.voucher_parent_type')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('parent_type', isset($voucher) ? $voucher->parent_type : null); ?>

	<select name="parent_type" id="parent_type" class="form-control" >
	@foreach ([ ["id" => "App\Plant", "name" => "Plant"], ["id" => "App\Location", "name" => "Location"] ] as $vtype)
		<option value="{{$vtype['id']}}" {{ $vtype['id'] == $selected ? 'selected' : '' }}>
            {{ $vtype['name'] }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.voucher_parent_type_hint')
    </div>
  </div>
</div>

<div class="form-group" id="location_group"> <!-- for LOCATION based vouchers -->
    <label for="parent_location_id" class="col-sm-3 control-label">
@lang('messages.parent_location')
</label>
        <a data-toggle="collapse" href="#hintpl" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('parent_location_id', isset($voucher) ? $voucher->parent_id : null); ?>

	<select name="parent_location_id" id="parent_location_id" class="form-control" >
        <option value=""> </option>
	@foreach ($locations as $location)
		<option value="{{$location->id}}" {{ $location->id == $selected ? 'selected' : '' }}>
            {{ $location->name }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hintpl" class="panel-collapse collapse">
	@lang('messages.voucher_parent_location_hint')
    </div>
  </div>
</div>

<div class="form-group" id="plant_group"> <!-- for PLANT based vouchers -->
    <label for="parent_plant_id" class="col-sm-3 control-label">
@lang('messages.parent_plant')
</label>
        <a data-toggle="collapse" href="#hintpp" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('parent_plant_id', isset($voucher) ? $voucher->parent_id : null); ?>

	<select name="parent_plant_id" id="parent_plant_id" class="form-control" >
        <option value=""> </option>
	@foreach ($plants as $plant)
		<option value="{{$plant->id}}" {{ $plant->id == $selected ? 'selected' : '' }}>
            {{ $plant->fullname }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hintpp" class="panel-collapse collapse">
	@lang('messages.voucher_parent_plant_hint')
    </div>
  </div>
</div>

<div class="form-group" id="project_group">
    <label for="project" class="col-sm-3 control-label">
@lang('messages.project')
</label>
        <a data-toggle="collapse" href="#hintprj" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('project_id', isset($voucher) ? $voucher->project_id : null); ?>

	<select name="project_id" id="project_id" class="form-control" >
	@foreach ($projects as $project)
		<option value="{{$project->id}}" {{ $project->id == $selected ? 'selected' : '' }}>
            {{ $project->name }}
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hintprj" class="panel-collapse collapse">
	@lang('messages.voucher_project_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($voucher) ? $voucher->notes : null) }}</textarea>
            </div>
</div>

<span id="identification_group">
<div class="form-group">
    <label for="taxon_id" class="col-sm-3 control-label">
@lang('messages.taxon')
</label>
        <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="taxon_autocomplete" id="taxon_autocomplete" class="form-control"
    value="{{ old('taxon_autocomplete', (isset($voucher) and $voucher->identification) ? $voucher->identification->taxon->fullname : null) }}">
    <input type="hidden" name="taxon_id" id="taxon_id"
    value="{{ old('taxon_id', (isset($voucher) and $voucher->identification) ? $voucher->identification->taxon_id : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint6" class="panel-collapse collapse">
	@lang('messages.voucher_taxon_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="modifier" class="col-sm-3 control-label">
@lang('messages.modifier')
</label>
        <a data-toggle="collapse" href="#hint9" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('modifier', (isset($voucher) and $voucher->identification) ? $voucher->identification->modifier : null); ?>
    
@foreach (App\Identification::MODIFIERS as $modifier)
        <span>
    		<input type = "radio" name="modifier" value="{{$modifier}}" {{ $modifier == $selected ? 'checked' : '' }}>
            @lang('levels.identification.' . $modifier)
		</span>
	@endforeach
            </div>
  <div class="col-sm-12">
    <div id="hint9" class="panel-collapse collapse">
	@lang('messages.voucher_modifier_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="identifier_id" class="col-sm-3 control-label">
@lang('messages.identifier')
</label>
        <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('identifier_id', (isset($voucher) and $voucher->identification) ? $voucher->identification->person_id : null); ?>

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
	@lang('messages.voucher_identifier_id_hint')
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
	<?php $selected = old('identification_date_year', (isset($voucher) and $voucher->identification) ? $voucher->identification->year : null); ?>
	@for ($i = config('app.max_year'); $i >= config('app.min_year'); $i --)
        <option value="{{$i}}" {{ $i == $selected ? 'selected' : '' }}>
            {{$i}}
        </option>
	@endfor
	</select> /
	<select name="identification_date_month" id="identification_date_month" class="form-control partdatepicker">
	<?php $selected = old('identification_date_month', (isset($voucher) and $voucher->identification) ? $voucher->identification->month : null); ?>
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
	<?php $selected = old('identification_date_day', (isset($voucher) and $voucher->identification) ? $voucher->identification->day : null); ?>
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
	@lang('messages.voucher_identification_date_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="herbarium_id" class="col-sm-3 control-label">
@lang('messages.id_herbarium')
</label>
        <a data-toggle="collapse" href="#hint10" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('herbarium_id', (isset($voucher) and $voucher->identification) ? $voucher->identification->herbarium_id : null); ?>

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
	@lang('messages.voucher_herbarium_id_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="identification_notes" class="col-sm-3 control-label">
@lang('messages.identification_notes')
</label>
	    <div class="col-sm-6">
	<textarea name="identification_notes" id="identification_notes" class="form-control">{{ old('identification_notes', (isset($voucher) and $voucher->identification) ? $voucher->identification->notes : null) }}</textarea>
            </div>
</div>
</span>

<div class="form-group">
<label for="herbaria" class="col-sm-3 control-label">
@lang ('messages.herbaria')
</label>
    <div class="col-sm-6">
<table class="table table-striped">
<thead>
    <th>
@lang('messages.herbarium')
    </th>
    <th>
@lang('messages.herbarium_number')
    </th>
</thead>
<tbody>
@foreach ($herbaria as $herbarium) 
    <tr>
        <td>{{$herbarium->acronym}}</td>
        <td><input name="herbarium[{{$herbarium->id}}]" value="{{ old('herbarium.' . $herbarium->id, (isset($voucher) and $voucher->herbaria->find($herbarium->id)) ? $voucher->herbaria->find($herbarium->id)->pivot->herbarium_number : null ) }}
"></td>
    </tr>
@endforeach
    <tr>
</tbody>
</table>
    </div>
</div>
