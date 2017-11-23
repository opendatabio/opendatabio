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
@lang('messages.hint_voucher_create')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_voucher')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($voucher))
		    <form action="{{ url('vouchers/' . $voucher->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('vouchers')}}" method="POST" class="form-horizontal">
@endif
             {{ csrf_field() }}

<?php
// TODO: I have no idea why this is not working in a single line!
$is_location = false;
if (isset($voucher) and $voucher->parent_type == "App\Location")
    $is_location = true;
if (isset($parent) and get_class($parent) == "App\Location")
    $is_location = true;
?>
@if ($is_location)
<div class="form-group"> 
    <label for="parent_location_id" class="col-sm-3 control-label">
@lang('messages.parent_location')
</label>
        <a data-toggle="collapse" href="#hintpl" class="btn btn-default">?</a>
	    <div class="col-sm-6">
        {{ isset($voucher) ? $voucher->parent->fullname : $parent->fullname }}
    <input type="hidden" name="parent_type" value="App\Location">
    <input type="hidden" name="parent_location_id" 
    value="{{ old('parent_location_id', isset($voucher) ? $voucher->parent_id : $parent->id) }}">
            </div>
  <div class="col-sm-12">
    <div id="hintpl" class="panel-collapse collapse">
	@lang('messages.voucher_parent_location_hint')
    </div>
  </div>
</div>
@else
<div class="form-group">
    <label for="parent_plant_id" class="col-sm-3 control-label">
@lang('messages.parent_plant')
</label>
        <a data-toggle="collapse" href="#hintpp" class="btn btn-default">?</a>
	    <div class="col-sm-6">
<?php 
$p = isset($voucher) ? $voucher->parent : $parent;
echo $p->fullname;
if ($p->identification)
    echo " <em>(" . $p->identification->taxon->fullname . ")</em>";
?>
    <input type="hidden" name="parent_type" value="App\Plant">
    <input type="hidden" name="parent_plant_id" 
    value="{{ old('parent_plant_id', isset($voucher) ? $voucher->parent_id : $parent->id) }}">
            </div>
  <div class="col-sm-12">
    <div id="hintpp" class="panel-collapse collapse">
	@lang('messages.voucher_parent_plant_hint')
    </div>
  </div>
</div>
@endif

<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label mandatory">
@lang('messages.main_collector')
</label>
        <a data-toggle="collapse" href="#hintp" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="person_autocomplete" id="person_autocomplete" class="form-control autocomplete"
    value="{{ old('person_autocomplete', isset($voucher) ? $voucher->person->full_name : (Auth::user()->person ? Auth::user()->person->full_name : null)) }}">
    <input type="hidden" name="person_id" id="person_id"
    value="{{ old('person_id', isset($voucher)  ? $voucher->person_id : Auth::user()->person_id) }}">
            </div>
  <div class="col-sm-12">
    <div id="hintp" class="panel-collapse collapse">
	@lang('messages.voucher_person_hint')
    </div>
  </div>
</div>

<div class="form-group">
    <label for="number" class="col-sm-3 control-label mandatory">
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
    <label for="date" class="col-sm-3 control-label mandatory">
@lang('messages.collection_date')
</label>
        <a data-toggle="collapse" href="#hint4" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! View::make('common.incompletedate')->with(['object' => isset($voucher) ? $voucher : null, 'field_name' => 'date']) !!}
            </div>
  <div class="col-sm-12">
    <div id="hint4" class="panel-collapse collapse">
	@lang('messages.voucher_date_hint')
    </div>
  </div>
</div>



<div class="form-group" id="project_group">
    <label for="project" class="col-sm-3 control-label mandatory">
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

@if ($is_location)
<div class="form-group">
    <label for="taxon_id" class="col-sm-3 control-label mandatory">
@lang('messages.taxon')
</label>
        <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="taxon_autocomplete" id="taxon_autocomplete" class="form-control autocomplete"
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
    <label for="identifier_id" class="col-sm-3 control-label mandatory">
@lang('messages.identifier')
</label>
        <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="identifier_autocomplete" id="identifier_autocomplete" class="form-control autocomplete"
    value="{{ old('identifier_autocomplete', (isset($voucher) and $voucher->identification) ? $voucher->identification->person->full_name : (Auth::user()->person ? Auth::user()->person->full_name : null)) }}">
    <input type="hidden" name="identifier_id" id="identifier_id"
    value="{{ old('identifier_id', (isset($voucher) and $voucher->identification) ? $voucher->identification->person_id : Auth::user()->person_id) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint7" class="panel-collapse collapse">
	@lang('messages.voucher_identifier_id_hint')
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
    'object' => (isset($voucher) and $voucher->identification) ? $voucher->identification : null, 
    'field_name' => 'identification_date'
]) !!}
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
@endif <!-- is_location -->

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
$("#person_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#person_id", "@lang('messages.noresults')");
});
</script>
@endpush
