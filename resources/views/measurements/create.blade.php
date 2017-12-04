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
<div id="ajax-error" class="collapse alert alert-danger">
@lang('messages.whoops')
</div>

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
    <label for="trait_id" class="col-sm-3 control-label mandatory">
@lang('messages.trait')
</label>
    <div id="spinner"></div>
    <div class="col-sm-6">
    <input type="text" name="trait_autocomplete" id="trait_autocomplete" class="form-control autocomplete"
    value="{{ old('trait_autocomplete', (isset($measurement) and $measurement->odbtrait) ? $measurement->odbtrait->name : null) }}" {{ isset($measurement) ? 'disabled' : null }} >
    <input type="hidden" name="trait_id" id="trait_id"
    value="{{ old('trait_id', isset($measurement) ? $measurement->trait_id : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="date" class="col-sm-3 control-label mandatory">
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
    <input type="text" name="bibreference_autocomplete" id="bibreference_autocomplete" class="form-control autocomplete"
    value="{{ old('bibreference_autocomplete', (isset($measurement) and $measurement->bibreference) ? $measurement->bibreference->bibkey : null) }}">
    <input type="hidden" name="bibreference_id" id="bibreference_id"
    value="{{ old('bibreference_id', isset($measurement) ? $measurement->bibreference_id : null) }}">
</div>
  <div class="col-sm-12">
    <div id="hintr" class="panel-collapse collapse">
	@lang('messages.measurement_bibreference_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label mandatory">
@lang('messages.measurement_measurer')
</label>
        <a data-toggle="collapse" href="#hintp" class="btn btn-default">?</a>
<div class="col-sm-6">
    <input type="text" name="person_autocomplete" id="person_autocomplete" class="form-control autocomplete"
    value="{{ old('person_autocomplete', (isset($measurement) and $measurement->person) ? $measurement->person->full_name : (Auth::user()->person ? Auth::user()->person->full_name : null)) }}">
    <input type="hidden" name="person_id" id="person_id"
    value="{{ old('person_id', isset($measurement) ? $measurement->person_id : Auth::user()->person_id) }}">
</div>
  <div class="col-sm-12">
    <div id="hintp" class="panel-collapse collapse">
	@lang('messages.measurement_person_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="dataset_id" class="col-sm-3 control-label mandatory">
@lang('messages.measurement_dataset')
</label>
        <a data-toggle="collapse" href="#hintd" class="btn btn-default">?</a>
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
    <div id="hintd" class="panel-collapse collapse">
	@lang('messages.measurement_dataset_hint')
    </div>
  </div>
</div>

<div class="form-group" id="append_value">
<?php if (isset($measurement)) {
echo View::make('traits.elements.' . $measurement->type, 
[
    'odbtrait' => $measurement->odbtrait,
    'measurement' => $measurement,
]);
    } elseif (!empty(old())) {
        $odbtrait = \App\ODBTrait::find(old('trait_id'));
        echo View::make('traits.elements.' . $odbtrait->type, 
            [
                'odbtrait' => $odbtrait,
                'measurement' => null,
            ]);

    }
?>
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
    $("#bibreference_autocomplete").odbAutocomplete("{{url('references/autocomplete')}}","#bibreference_id", "@lang('messages.noresults')");
    $("#link_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}","#link_id", "@lang('messages.noresults')");
    $("#person_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#person_id", "@lang('messages.noresults')");
    $("#trait_autocomplete").devbridgeAutocomplete({
        serviceUrl: "{{url('traits/autocomplete')}}",
        /* adds the object type to request; doubles the namespace back slashes */
        params: {'type': '{{ str_replace('\\', '\\\\', get_class($object)) }}' },
        onSelect: function (suggestion) {
            $("#trait_id").val(suggestion.data);
            $( "#spinner" ).css('display', 'inline-block');
            $.ajax({
            type: "GET",
                url: "{{url('traits/getformelement')}}",
                dataType: 'json',
                data: {'id': suggestion.data, 'measurement': null},
                success: function (data) {
                    $("#spinner").hide();
                    $("#ajax-error").collapse("hide");
                    $("#append_value").html(data.html);
                },
                error: function(e){ 
                    $("#spinner").hide();
                    $("#ajax-error").collapse("show");
                    $("#ajax-error").text('Error sending AJAX request');
                }
            })
        },
        onInvalidateSelection: function() {
            $("#trait_id").val(null);
        },
        minChars: 3,
        onSearchStart: function() {
            $(".minispinner").remove();
            $(this).after("<div class='spinner minispinner'></div>");
        },
        onSearchComplete: function() {
            $(".minispinner").remove();
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: "@lang('messages.noresults')"
    });
});
// NOTE! duplicated from view 6
@if (isset($measurement) and $measurement->type==6)
	$("#value").spectrum({
		flat:true,
		showInput:true,
		showPalette: true,
		showPaletteOnly: true,
		togglePaletteOnly: true,
		togglePaletteMoreText: "@lang('spectrum.more')",
		togglePaletteLessText: "@lang('spectrum.less')",
		palette: {!! json_encode(config('app.spectrum')) !!}
});
@endif
</script>
@endpush
