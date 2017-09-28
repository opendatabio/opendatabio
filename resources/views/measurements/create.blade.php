@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_measurement')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($measurement))
		    <form action="{{ url('measurements/' . $measurement->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('measurements')}}" method="POST" class="form-horizontal">
@endif
		     {{ csrf_field() }}
<div class="form-group">
    <p><strong>
@lang('messages.measurement_for')
:</strong> {{ $object->fullname }}
@if ($object->identification)
    (<em>{{$object->identification->taxon->fullname}}</em>)
@endif
    </p>
    <input type="hidden"  name="measured_id" value="{{$object->id}}">
    <input type="hidden"  name="measured_type" value="{{get_class($object)}}">
</div>
<div class="form-group">
    <label for="trait_id" class="col-sm-3 control-label">
@lang('messages.trait')
</label>
<? dd($traits); ?>
    <div class="col-sm-6">
	<?php $selected = old('trait_id', isset($measurement) ? $measurement->trait_id : null); ?>

	<select name="trait_id" id="trait_id" class="form-control" >
        <option value="">&nbsp;</option>
	@foreach ($traits as $odbtrait)
        <option value="{{$odbtrait->id}}" {{ $odbtrait->id == $selected ? 'selected' : '' }}>
        {{ $odbtrait->name }}
        </option>
	@endforeach
	</select>
    </div>
</div>
<div class="form-group">
    <label for="date" class="col-sm-3 control-label">
@lang('messages.measurement_date')
</label>
        <a data-toggle="collapse" href="#hintdate" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! View::make('common.incompletedate')->with([
    'object' => isset($measurement) ? $measurement : null, 
    'field_name' => 'date'
]) !!}
            </div>
  <div class="col-sm-12">
    <div id="hintdate" class="panel-collapse collapse">
	@lang('messages.measurement_date_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="bibreference_id" class="col-sm-3 control-label">
@lang('messages.measurement_bibreference')
</label>
        <a data-toggle="collapse" href="#hintr" class="btn btn-default">?</a>
<div class="col-sm-6">
	<?php $selected = old('bibreference_id', isset($measurement) ? $measurement->bibreference_id : null); ?>
	<select name="bibreference_id" id="bibreference_id" class="form-control" >
        <option value=''></option>
	@foreach ( $references as $reference )
		<option value="{{$reference->id}}" {{ $reference->id == $selected ? 'selected' : '' }}>
            {{ $reference->bibkey }}
		</option>
	@endforeach
	</select>
</div>
  <div class="col-sm-12">
    <div id="hintr" class="panel-collapse collapse">
	@lang('messages.measurement_bibreference_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label">
@lang('messages.measurement_person')
</label>
        <a data-toggle="collapse" href="#hintp" class="btn btn-default">?</a>
<div class="col-sm-6">
	<?php $selected = old('person_id', isset($measurement) ? $measurement->person_id : null); ?>
	<select name="person_id" id="person_id" class="form-control" >
        <option value=''></option>
	@foreach ( $persons as $person )
		<option value="{{$person->id}}" {{ $person->id == $selected ? 'selected' : '' }}>
            {{ $person->abbreviation }}
		</option>
	@endforeach
	</select>
</div>
  <div class="col-sm-12">
    <div id="hintr" class="panel-collapse collapse">
	@lang('messages.measurement_person_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="dataset_id" class="col-sm-3 control-label">
@lang('messages.measurement_dataset')
</label>
        <a data-toggle="collapse" href="#hintr" class="btn btn-default">?</a>
<div class="col-sm-6">
	<?php $selected = old('dataset_id', isset($measurement) ? $measurement->dataset_id : null); ?>
	<select name="dataset_id" id="dataset_id" class="form-control" >
	@foreach ( $datasets as $dataset )
		<option value="{{$dataset->id}}" {{ $dataset->id == $selected ? 'selected' : '' }}>
            {{ $dataset->name }}
		</option>
	@endforeach
	</select>
</div>
  <div class="col-sm-12">
    <div id="hintr" class="panel-collapse collapse">
	@lang('messages.measurement_dataset_hint')
    </div>
  </div>
</div>

<div class="form-group">
<label for="value" class="col-sm-3 control-label">
@lang('messages.value')
</label>
<div class="col-sm-6">
<input name ="value" id="value" type="text" class="form-control" value="{{old('value', isset($measurement) ? $measurement->valueActual : null)}}">
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
