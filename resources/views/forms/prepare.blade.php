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
<table class="table table-striped">
<thead>
    <th>
@lang('messages.plant')
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
    <input type="hidden"  name="measured_id[]" value="{{$form_item->id}}">
    <td>{{ $form_item->fullname }} 
    @if ($form_item->taxonname)
        <br>(<em>{{ $form_item->taxonname}}</em>)
    @endif
    </td>

    @foreach ($form->traits as $odbtrait)
    <td>
<?php 
// TODO: adapt views other than 0 and 2 to use index
// TODO: write the controller methods
// TODO: add date picker
echo View::make('traits.elements.' . $odbtrait->type, 
[
    'odbtrait' => $odbtrait,
    'measurement' => null,
    'index' => $form_item->id,
]);
?>
    </td>
    @endforeach


</tr>
@endforeach <!-- items -->
</tbody>
</table>

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
    $("#person_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#person_id", "@lang('messages.noresults')");
</script>
@endpush
