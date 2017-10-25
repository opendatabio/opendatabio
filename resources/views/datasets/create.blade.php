@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_dataset')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($dataset))
		    <form action="{{ url('datasets/' . $dataset->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('datasets')}}" method="POST" class="form-horizontal">
@endif
		     {{ csrf_field() }}
<div class="form-group">
    <label for="name" class="col-sm-3 control-label mandatory">
@lang('messages.name')
</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($dataset) ? $dataset->name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($dataset) ? $dataset->notes : null) }}</textarea>
            </div>
</div>
<div class="form-group">
    <label for="privacy" class="col-sm-3 control-label mandatory">
@lang('messages.privacy')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('privacy', isset($dataset) ? $dataset->privacy : 0); ?>

	<select name="privacy" id="privacy" class="form-control" >
	@foreach (App\Dataset::PRIVACY_LEVELS as $level)
        <option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
@lang('levels.privacy.' . $level)
</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.dataset_privacy_hint')
    </div>
  </div>
</div>
<div class="form-group">
<label for="tags" class="col-sm-3 control-label">
@lang('messages.tags')
</label>
<div class="col-sm-6">
{!! Multiselect::select(
    'tags', 
    $tags->pluck('name', 'id'), isset($dataset) ? $dataset->tags->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div>
<div class="form-group">
    <label for="bibreference_id" class="col-sm-3 control-label">
@lang('messages.dataset_bibreference')
</label>
        <a data-toggle="collapse" href="#hintr" class="btn btn-default">?</a>
<div class="col-sm-6">
    <input type="text" name="bibreference_autocomplete" id="bibreference_autocomplete" class="form-control autocomplete"
    value="{{ old('bibreference_autocomplete', (isset($taxon) and $taxon->bibreference) ? $taxon->bibreference->bibkey : null) }}">
    <input type="hidden" name="bibreference_id" id="bibreference_id"
    value="{{ old('bibreference_id', isset($taxon) ? $taxon->bibreference_id : null) }}">
</div>
  <div class="col-sm-12">
    <div id="hintr" class="panel-collapse collapse">
	@lang('messages.dataset_bibreference_hint')
    </div>
  </div>
</div>

<div class="form-group">
<label for="admins" class="col-sm-3 control-label mandatory">
@lang('messages.admins')
</label>
<a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::select(
    'admins', 
    $fullusers->pluck('email', 'id'), isset($dataset) ? $dataset->admins->pluck('id') : [Auth::user()->id],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.collabs')
</label>
<div class="col-sm-6">
{!! Multiselect::select(
    'collabs', 
    $fullusers->pluck('email', 'id'), isset($dataset) ? $dataset->collabs->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.viewers')
</label>
<div class="col-sm-6">
{!! Multiselect::select(
    'viewers', 
    $allusers->pluck('email', 'id'), isset($dataset) ? $dataset->viewers->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
<div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.dataset_admins_hint')
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
$("#bibreference_autocomplete").devbridgeAutocomplete({
    serviceUrl: "{{url('references/autocomplete')}}",
    onSelect: function (suggestion) {
        $("#bibreference_id").val(suggestion.data);
    },
    onInvalidateSelection: function() {
        $("#bibreference_id").val(null);
    }
    });
</script>
@endpush
