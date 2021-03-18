@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-12">
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
@lang('messages.hint_form_prepare')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.fill_form')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

		    <form action="{{ url('forms/' . $form->id . '/fill')}}" method="POST" class="form-horizontal">
		     {{ csrf_field() }}
<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label mandatory">
        @lang('messages.person')
    </label>
        <a data-toggle="collapse" href="#hinte" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="person_autocomplete" id="person_autocomplete" class="form-control autocomplete"
    value="{{ old('person_autocomplete', (Auth::user()->person ? Auth::user()->person->full_name : null)) }}">
    <input type="hidden" name="person_id" id="person_id"
    value="{{ old('person_id', Auth::user()->person_id) }}">
        </div>
  <div class="col-sm-12">
    <div id="hinte" class="panel-collapse collapse">
	@lang('messages.trait_export_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="date" class="col-sm-3 control-label mandatory">
@lang('messages.measurement_date')
</label>
        <a data-toggle="collapse" href="#hintdate" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! View::make('common.incompletedate')->with([
    'object' => null,
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
@lang('messages.bibreference')
</label>
<div class="col-sm-6">
    <input type="text" name="bibreference_autocomplete" id="bibreference_autocomplete" class="form-control autocomplete"
    value="{{ old('bibreference_autocomplete') }}">
    <input type="hidden" name="bibreference_id" id="bibreference_id"
    value="{{ old('bibreference_id') }}">
</div>
</div>
<div class="form-group">
    <label for="dataset_id" class="col-sm-3 control-label mandatory">
@lang('messages.measurement_dataset')
</label>
        <a data-toggle="collapse" href="#hintd" class="btn btn-default">?</a>
<div class="col-sm-6">
@if (count($datasets))
	<?php $selected = old('dataset_id', Auth::user()->defaultDataset ? Auth::user()->defaultDataset->id : null); ?>
	<select name="dataset_id" id="dataset_id" class="form-control" >
	@foreach ( $datasets as $dataset )
		<option value="{{$dataset->id}}" {{ $dataset->id == $selected ? 'selected' : '' }}>
            {{ $dataset->name }}
		</option>
	@endforeach
	</select>
    @else
        <div class="alert alert-danger">
        @lang ('messages.no_valid_dataset')
        </div>
        @endif
</div>
</div>
@if ($form->notes)
    <div class="alert alert-warning">
    <p><strong>@lang('messages.notes'):</strong>
        {{ $form->notes }}
    </p></div>
@endif


<table class="table table-striped">
<thead>
    <th>
@lang('messages.individual')
    </th>
@foreach ($form->traits as $odbtrait)
    <th>
{{ $odbtrait->name }}
    </th>
    @endforeach
</thead>
<tbody>

@foreach ($items as $form_item)
<tr>
    <td>{!! $form_item->rawLink(true) !!} </td>

    @foreach ($form->traits as $traitorder => $odbtrait)
    <td>
<?php
// This returns null for no records:
$measurement = $measurements->filter(function($m) use ($odbtrait, $form_item) {
    return $m->trait_id == $odbtrait->id and $m->measured_id == $form_item->id;
})->first();
// TODO: adapt views other than 0 and 2 to use index
echo View::make('traits.elements.' . $odbtrait->type,
[
    'odbtrait' => $odbtrait,
    'measurement' => $measurement,
    'index' => $form_item->id,
    'traitorder' => $traitorder,
]);
?>
    </td>
    @endforeach


</tr>
@endforeach <!-- items -->
</tbody>
</table>

		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
                <button type="submit" class="btn btn-success" name="submit" value="submit"
@if(!count($datasets))
disabled
@endif
>
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
    $("#person_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#person_id", "@lang('messages.noresults')");
    $("#bibreference_autocomplete").odbAutocomplete("{{url('references/autocomplete')}}","#bibreference_id", "@lang('messages.noresults')");
    $(".spectrum").each(function () {
        if($(this).val() == '') {
        $(this).spectrum({
            flat:true,
            showInput:true,
            showInitial: false,
            allowEmpty: true,
            showPalette: true,
            showPaletteOnly: true,
            togglePaletteOnly: true,
            togglePaletteMoreText: "@lang('spectrum.more')",
            togglePaletteLessText: "@lang('spectrum.less')",
            preferredFormat: "hex",
            showButtons: false,
            palette: {!! json_encode(config('app.spectrum')) !!}
        });
        } else {
        $(this).spectrum({
            flat:true,
            showInput:true,
            showInitial: false,
            allowEmpty: true,
            showPalette: true,
            showPaletteOnly: true,
            togglePaletteOnly: false,
            togglePaletteMoreText: "@lang('spectrum.more')",
            togglePaletteLessText: "@lang('spectrum.less')",
            preferredFormat: "hex",
            showButtons: false,
            palette: [$(this).val()]
        });
        }
    });
    $("input[name^=link_autocomplete]").each(function(index) {
        var element_id = $(this).attr('id');
        var firstc = element_id.indexOf("[");
        var secondc = element_id.lastIndexOf("[");
        var ix = parseInt(element_id.substring(firstc + 1,secondc - 1));
        var jx = parseInt(element_id.substring(secondc + 1, element_id.length - 1));
        $(this).odbAutocomplete("{{url('taxons/autocomplete')}}","#link_id\\[${ix}\\]\\[${jx}\\]", "@lang('messages.noresults')");
    });
});
</script>
@endpush
