@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">
@lang('messages.help')
</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.hint_plant_create')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_plant')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($plant))
		    <form action="{{ url('plants/' . $plant->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('plants')}}" method="POST" class="form-horizontal">
@endif
		     {{ csrf_field() }}
<!-- name -->
<div class="form-group">
    <label for="tag" class="col-sm-3 control-label mandatory">
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
    <label for="location_id" class="col-sm-3 control-label mandatory">
@lang('messages.location')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="location_autocomplete" id="location_autocomplete" class="form-control autocomplete"
    value="{{ old('location_autocomplete', (isset($plant) and $plant->location) ? $plant->location->fullname : null) }}">
    <input type="hidden" name="location_id" id="location_id"
    value="{{ old('location_id', isset($plant) ? $plant->location_id : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.plant_location_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="project" class="col-sm-3 control-label mandatory">
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
    <label for="date" class="col-sm-3 control-label mandatory">
@lang('messages.tag_date')
</label>
        <a data-toggle="collapse" href="#hint4" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! View::make('common.incompletedate')->with([
    'object' => isset($plant) ? $plant : null, 
    'field_name' => 'date'
]) !!}
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
    <label for="collectors" class="col-sm-3 control-label mandatory">
@lang('messages.tag_team')
</label>
        <a data-toggle="collapse" href="#hint5" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! Multiselect::select('collector', 
    $persons->pluck('abbreviation', 'id'), 
    isset($plant) ? $plant->collectors->pluck('person_id') : 
    (empty(Auth::user()->person_id) ? '' : [Auth::user()->person_id] )
, 
    ['class' => 'multiselect form-control']) 
!!}
            </div>
  <div class="col-sm-12">
    <div id="hint5" class="panel-collapse collapse">
	@lang('messages.plant_collectors_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="taxon_id" class="col-sm-3 control-label mandatory">
@lang('messages.taxon')
</label>
        <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="taxon_autocomplete" id="taxon_autocomplete" class="form-control autocomplete"
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
    <label for="identifier_id" class="col-sm-3 control-label mandatory">
@lang('messages.identifier')
</label>
        <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="identifier_autocomplete" id="identifier_autocomplete" class="form-control autocomplete"
    value="{{ old('identifier_autocomplete', (isset($plant) and $plant->identification) ? $plant->identification->person->full_name . ' [' . $plant->identification->person->abbreviation . ']'  : (Auth::user()->person ? Auth::user()->person->full_name : null)) }}">
    <input type="hidden" name="identifier_id" id="identifier_id"
    value="{{ old('identifier_id', (isset($plant) and $plant->identification) ? $plant->identification->person_id : Auth::user()->person_id) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint7" class="panel-collapse collapse">
	@lang('messages.plant_identifier_id_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="identification_date" class="col-sm-3 control-label mandatory">
@lang('messages.identification_date')
</label>
        <a data-toggle="collapse" href="#hint8" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! View::make('common.incompletedate')->with([
    'object' => (isset($plant) and $plant->identification) ? $plant->identification : null, 
    'field_name' => 'identification_date'
]) !!}
            </div>
  <div class="col-sm-12">
    <div id="hint8" class="panel-collapse collapse">
	@lang('messages.plant_identification_date_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="herbarium_id" class="col-sm-3 control-label">
@lang('messages.id_herbarium')
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
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')

				</button>
				<a href="{{url()->previous()}}" class="btn btn-warning">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.back')
				</a>
			    </div>
			</div>
		    </form>
        </div>
    </div>
@endsection

@push ('scripts')
<script>
$(document).ready(function() {
$("#location_autocomplete").odbAutocomplete("{{url('locations/autocomplete')}}", "#location_id", "@lang('messages.noresults')");
$("#taxon_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}", "#taxon_id","@lang('messages.noresults')",
        function() {
            // When the identification of a plant or voucher is changed, all related fields are reset
            $('input:radio[name=modifier][value=0]').trigger('click');
            $("#identifier_id").val('');
            $("#identification_date_year").val((new Date).getFullYear());
            $("#identification_date_month").val(0);
            $("#identification_date_day").val(0);
            $("#herbarium_id").val('');
            $("#identification_notes").val('');
        });
$("#identifier_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#identifier_id", "@lang('messages.noresults')");
});
</script>
@endpush
