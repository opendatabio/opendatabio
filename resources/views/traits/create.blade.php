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
@lang('messages.hint_trait_create')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_trait')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($odbtrait))
		    <form action="{{ url('traits/' . $odbtrait->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('traits')}}" method="POST" class="form-horizontal">
@endif
		     {{ csrf_field() }}
<div class="form-group">
    <div class="col-sm-12">
<table class="table table-striped">
<thead>
    <th>
@lang('messages.language')
    </th>
    <th>
@lang('messages.name')
    </th>
    <th>
@lang('messages.description')
    </th>
</thead>
<tbody>
@foreach ($languages as $language) 
    <tr>
        <td>{{$language->name}}</td>
        <td><input name="name[{{$language->id}}]" value="{{ old('name.' . $language->id, isset($odbtrait) ? $odbtrait->translate(\App\UserTranslation::NAME, $language->id) : null) }}"></td>
        <td><input name="description[{{$language->id}}]" value="{{ old('description.' . $language->id, isset($odbtrait) ? $odbtrait->translate(\App\UserTranslation::DESCRIPTION, $language->id) : null) }}"></td>
    </tr>
@endforeach
    <tr>
</tbody>
</table>
    </div>
</div>

<div class="form-group">
    <label for="export_name" class="col-sm-3 control-label">
        @lang('messages.export_name')
    </label>
	    <div class="col-sm-6">
        <input name="export_name" value="{{ old('export_name', isset($odbtrait) ? $odbtrait->export_name : null) }}">
        </div>
</div>
    
<div class="form-group">
    <label for="type" class="col-sm-3 control-label">
@lang('messages.type')
</label>
        <a data-toggle="collapse" href="#hintp" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('type', isset($odbtrait) ? $odbtrait->type : null); ?>

	<select name="type" id="type" class="form-control" >
	@foreach (\App\ODBTrait::TRAIT_TYPES as $ttype)
		<option value="{{ $ttype }}" {{ $ttype == $selected ? 'selected' : '' }}>
@lang('levels.traittype.' . $ttype)
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hintp" class="panel-collapse collapse">
	@lang('messages.trait_type_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="objects" class="col-sm-3 control-label">
@lang('messages.object_types')
</label>
        <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! Multiselect::select(
    'objects', 
    \App\ODBTrait::OBJECT_TYPES, 
    isset($odbtrait) ? $odbtrait->object_types()->pluck('object_type') : [],
    ['class' => 'multiselect form-control']
) !!}
            </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.trait_objects_hint')
    </div>
  </div>
</div>
<div class="form-group trait-number">
    <label for="unit" class="col-sm-3 control-label">
@lang('messages.unit')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="unit" id="unit" class="form-control" value="{{ old('unit', isset($odbtrait) ? $odbtrait->unit : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
@lang('messages.hint_trait_number')
    </div>
  </div>
</div>
<div class="form-group trait-number">
    <label for="range_min" class="col-sm-3 control-label">
@lang('messages.range_min')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="range_min" id="range_min" class="form-control" value="{{ old('range_min', isset($odbtrait) ? $odbtrait->range_min : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
@lang('messages.hint_trait_number')
    </div>
  </div>
</div>
<div class="form-group trait-number">
    <label for="range_max" class="col-sm-3 control-label">
@lang('messages.range_max')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="range_max" id="range_max" class="form-control" value="{{ old('range_max', isset($odbtrait) ? $odbtrait->range_max : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
@lang('messages.hint_trait_number')
    </div>
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
	function setFields(vel) {
		var adm = $('#type option:selected').val();
		if ("undefined" === typeof adm) {
			return; // nothing to do here...
		}
		switch (adm) {
			case "0": // numeric
			case "1": // numeric FALL THROUGH
				$(".trait-number").show(vel);
				break;
			case "2": // categories
				$(".trait-number").hide(vel);
				break;
			default: // other
				$(".trait-number").hide(vel);
		}
	}
	$("#type").change(function() { setFields(400); });
    // trigger this on page load
	setFields(0);
});
</script>
@endpush
