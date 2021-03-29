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


<!-- INDIVIDUAL THE VOUCHER BELONGS TO -->
<div class="form-group">
  <label for="individual_id" class="col-sm-3 control-label mandatory">
    @lang('messages.individual')
  </label>
  <a data-toggle="collapse" href="#hintpp" class="btn btn-default">?</a>

  <div class="col-sm-6">
    <div id='individual_taxon' hidden class='alert-success'></div>
    <input type="text" name="individual_autocomplete" id="individual_autocomplete" class="form-control autocomplete" value="{{ old('individual_autocomplete', isset($voucher) ? $voucher->individual->fullname : (isset($individual) ? $individual->fullname : null) ) }}">
    <input type="hidden" name="individual_id" id="individual_id" value="{{ old('individual_id', isset($voucher) ? $voucher->individual_id : (isset($individual) ? $individual->id : null)) }}">
  </div>
  <div class="col-sm-12">
    <div id="hintpp" class="panel-collapse collapse">
	     @lang('messages.voucher_individual_hint')
    </div>
  </div>
</div>

<!-- THE BIOCOLLECTION THE VOUCHER BELONGS TO -->
<div class="form-group">
  <label for="biocollection_id" class="col-sm-3 control-label mandatory">
    @lang('messages.biocollection')
  </label>
  <a data-toggle="collapse" href="#hint_biocollection" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <select name="biocollection_id" class="form-control" >
      @php
         $ovtype = old('biocollection_id', isset($voucher) ? $voucher->biocollection_id : null);
      @endphp
      <option >&nbsp;</option>
      @foreach ($biocollections as $biocollection)
      <option value="{{ $biocollection->id }}" {{ $biocollection->id == $ovtype ? 'selected' : '' }}>
        {{ $biocollection->acronym."  [".substr($biocollection->name,0,30)."..]"}}
      </option>
      @endforeach
    </select>
  </div>
  <div class="col-sm-12">
    <div id="hint_biocollection" class="panel-collapse collapse">
	     @lang('messages.voucher_biocollections_deposited_hint')
    </div>
  </div>
</div>

<div class="form-group">
  <label for="biocollection_number" class="col-sm-3 control-label ">
    @lang('messages.biocollection_number')
  </label>
  <a data-toggle="collapse" href="#hint_biocollection_number" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="text" name="biocollection_number" value="{{ old('biocollection_number', isset($voucher) ? $voucher->biocollection_number : null) }}" class="form-control">
  </div>
  <div class="col-sm-12">
    <div id="hint_biocollection_number" class="panel-collapse collapse">
	     @lang('messages.voucher_biocollections_number_hint')
    </div>
  </div>
</div>

<!-- is this a nomenclatural type -->
<div class="form-group">
  <label for="biocollection_type" class="col-sm-3 control-label mandatory">
    @lang('messages.voucher_isnomenclatural_type')?
  </label>
  <a data-toggle="collapse" href="#hint_biocollection_type" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <select name="biocollection_type" class="form-control" style="width:auto;">
      @php
         $ovtype = old('biocollection_type', isset($voucher) ? $voucher->biocollection_type : 0);
      @endphp
      @foreach (\App\Models\Biocollection::NOMENCLATURE_TYPE as $vtype)
      <option value="{{ $vtype }}" {{ $vtype == $ovtype ? 'selected' : '' }}>
        @lang('levels.vouchertype.' . $vtype)
      </option>
      @endforeach
    </select>
  </div>
  <div class="col-sm-12">
    <div id="hint_biocollection_type" class="panel-collapse collapse">
	     @lang('messages.voucher_nomenclatural_type')
    </div>
  </div>
</div>


@php
$collectors_self = '';
$collectors_individual = 'checked';
if (empty(old())) { // no "old" value, we're just arriving
    if (isset($voucher)) {
      if ($voucher->collectors()->count()) {
          $collectors_self = 'checked';
      } else {
          $collectors_individual = 'checked';
      }
   } else {
     if (isset($individual)) {
       $collectors_individual = 'checked';
     }
   }
} else { // "old" value is available, work with it
  if (!empty(old('collectors_selfother'))) {
    if (old('collectors_selfother') == 1) {
        $collectors_self = 'checked';
    } else {
        $collectors_individual = 'checked';
    }
  }
}
@endphp
<div class="form-group">
    <label class="col-sm-3 control-label"></label>
    <a data-toggle="collapse" href="#has_voucher" class="btn btn-default">?</a>
    <div class="col-sm-6">
        <div class="radio">
            <label>
                <input id='radio_1' type="radio" name="collectors_selfother" value=1  {{$collectors_individual}} >@lang('messages.voucher_collector_individual')
            </label>
            <label>
                <input id='radio_0' type="radio" name="collectors_selfother" value=0  {{$collectors_self}} >@lang('messages.voucher_collector_self')
            </label>
        </div>
    </div>
    <div class="col-sm-12">
      <div id="has_voucher" class="panel-collapse collapse">
         @lang('messages.voucher_individual_collector')
      </div>
    </div>
</div>

<div class="form-group collectors_individual">
  <label class="col-sm-3 control-label">
    @lang('messages.collector')
  </label>
  <div id='individual_collectors' hidden class='col-sm-6 alert-success'>
  </div>
</div>

<div class="form-group collectors_individual">
  <label class="col-sm-3 control-label">
    @lang('messages.voucher_number')
  </label>
  <div id='individual_tag' hidden class='col-sm-6 alert-success'>
  </div>
</div>

<div class="form-group collectors_individual">
  <label class="col-sm-3 control-label">
    @lang('messages.date')
  </label>
  <div id='individual_date' hidden class='col-sm-6 alert-success'>
  </div>
</div>


<!-- collector -->
<div class="form-group collectors_self">
    <label for="collectors" class="col-sm-3 control-label mandatory">
      @lang('messages.collector')
    </label>
    <a data-toggle="collapse" href="#hint_collectors" class="btn btn-default">?</a>
    <div class="col-sm-6">
      {!! Multiselect::autocomplete('collector',
        $persons->pluck('abbreviation', 'id'),
        (isset($voucher) and $voucher->collector_main->count()) ? $voucher->collectors->pluck('person_id')->toArray() : null,
        ['class' => 'multiselect form-control'])
      !!}
    </div>
    <div class="col-sm-12">
      <div id="hint_collectors" class="panel-collapse collapse">
         @lang('messages.voucher_collectors_hint')
       </div>
     </div>
</div>


<div class="form-group collectors_self">
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



<!-- date is also not mandatory -->
<div class="form-group collectors_self">
    <label for="date" class="col-sm-3 control-label mandatory">
      @lang('messages.collection_date')
    </label>
    <a data-toggle="collapse" href="#hintdate" class="btn btn-default">?</a>
	    <div class="col-sm-6">
        {!! View::make('common.incompletedate')->with(['object' => isset($voucher) ? $voucher : null, 'field_name' => 'date']) !!}
      </div>
    <div class="col-sm-12">
      <div id="hintdate" class="panel-collapse collapse">
	       @lang('messages.voucher_date_hint')
       </div>
  </div>
</div>


<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
      @lang('messages.notes')
    </label>
    <a data-toggle="collapse" href="#hintnotes" class="btn btn-default">?</a>
	   <div class="col-sm-6">
	      <textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($voucher) ? $voucher->notes : null) }}</textarea>
      </div>
      <div class="col-sm-12">
        <div id="hintnotes" class="panel-collapse collapse">
	         @lang('messages.voucher_note_hint')
         </div>
       </div>
</div>


<!-- PROJECT -->
<div class="form-group">
    <label for="project" class="col-sm-3 control-label mandatory">
      @lang('messages.project')
    </label>
    <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
    <div class="col-sm-6">
      @if (count($projects))
        @php
          $selected = old('project_id', isset($voucher) ? $voucher->project_id : (isset($individual) ? $individual->project_id : (Auth::user()->defaultProject ? Auth::user()->defaultProject->id : null)));
          @endphp
	        <select name="project_id" id="project_id" class="form-control" >
	           @foreach ($projects as $project)
		             <option value="{{$project->id}}" {{ $project->id == $selected ? 'selected' : '' }}>
                   {{ $project->name }}
		             </option>
	           @endforeach
	        </select>
        @else
          <div class="alert alert-danger">
            @lang ('messages.no_valid_project')
          </div>
        @endif
    </div>
    <div class="col-sm-12">
      <div id="hint3" class="panel-collapse collapse">
	       @lang('messages.voucher_project_hint')
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

$(".collectors_self").hide();
$('.collectors_individual').hide();
onChangeIndividual();

$("#individual_autocomplete").odbAutocomplete("{{url('individuals/autocomplete')}}", "#individual_id", "@lang('messages.noresults')",null,undefined,onChangeIndividual);

$('#collector').on('change',function(){
  $('#individual_collectors').html(null).hide();
});

function onChangeIndividual() {
    var inid = $('#individual_id').val();
    if (inid) {
    //e.preventDefault(); // does not allow the form to submit
    $.ajax({
      type: "GET",
      url: "{{ route('getIndividualForVoucher') }}",
      dataType: 'json',
      data: {
        'id': inid
        },
      success: function (data) {
        //alert(data.individual[1]);
        $('.collectors_individual').show();
        $("#radio_1").prop("checked", true);
        $('.collectors_self').hide();
        $('#individual_taxon').html(data.individual[0]).show();
        $('#individual_collectors').html(data.individual[1]).show();
        $('#individual_date').html(data.individual[2]).show();
        $('#individual_tag').html(data.individual[3]).show();
        $('#project_id').val(data.individual[4]);
      },
      error: function(e){
        alert( inid + " will be error" );
      }
      });
    }
  }

});


$("input[name=collectors_selfother]").change(function() {
    var fromindividual = $('input[name=collectors_selfother]:checked').val();
    if (fromindividual == 1) {
      if ($('#individual_id').val()) {
        $('.collectors_individual').show();
      }
      $('.collectors_self').hide();
    } else {
      $('.collectors_individual').hide();
      $('.collectors_self').show();
    }
});





</script>
{!! Multiselect::scripts('collector', url('persons/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
@endpush
