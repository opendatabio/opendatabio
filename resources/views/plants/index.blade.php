@extends('layouts.app')

@section('content')
    <div class="container-fluid">
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
@lang('messages.plants_hint')
      </div>
    </div>
  </div>

@if (isset($object)) <!-- we're inside a Location, Project or Taxon view -->
        <div class="panel panel-default">
            <div class="panel-heading">
  @lang('messages.plant_list')
            </div>
            <div class="panel-body">
    <p><strong>
    @lang('messages.plant_list_for'):</strong>
    {{ $object->fullname }}
    </p>
            </div>
        </div>


@else
@can ('create', App\Plant::class)
        <div class="panel panel-default">
            <div class="panel-heading">
  @lang('messages.create_plant')
            </div>

            <div class="panel-body">
            <div class="col-sm-6">
            <a href="{{url ('plants/create')}}" class="btn btn-success">
                <i class="fa fa-btn fa-plus"></i>
@lang('messages.create')
            </a>
          </div>
            <div class="col-sm-6">
            <button class="btn btn-success" id='batch_identify'>
              @lang('messages.plants_batch_identify')
            </button>
            </div>
          </div>
        </div>
    @endcan
@endif

<!--- Batch Identification of plants hidden only registered users will see and project collaborators will be able to use-->
<?php // TODO: Perhaps make only collaborators of any project having plants to see it. ?>
<div class="panel panel-default" id='batch_identification_panel' hidden>
  <div class="panel-heading">
    @lang('messages.plants_batch_identify')
    &nbsp;&nbsp;<a data-toggle="collapse" href="#hint_batch_identify" class="btn btn-default">?</a>
    <div id="hint_batch_identify" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.plants_batch_identify_hint')
      </div>
  </div>
</div>

  <div class="panel-body">

<form action="{{ url('batchidentifications')}}" method="POST" class="form-horizontal" id='batch_identification_form'>
  <!-- csrf protection -->
  {{ csrf_field() }}
<input type='hidden' name='plantids_list' id='batch_list' value='' value="">
<div class="form-group">
<label class="col-sm-3 control-label mandatory">
@lang('messages.taxon')
</label>
  <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
<div class="col-sm-6">
    <input type="text" name="taxon_autocomplete" id="taxon_autocomplete" class="form-control autocomplete" value="">
    <input type="hidden" name="taxon_id" id="taxon_id"   value="">
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
	<?php $selected = null; ?>
    @foreach (App\Identification::MODIFIERS as $modifier)
        <span>
    		<input type = "radio" name="modifier" value="{{$modifier}}" >
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
      value="{{ isset(Auth::user()->person) ? Auth::user()->person->full_name : null }}">
    <input type="hidden" name="identifier_id" id="identifier_id"
      value="{{ isset(Auth::user()->person) ? Auth::user()->person->id : null }}">
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
    'object' => null,
    'field_name' => 'identification_date'
]) !!}
    </div>
  <div class="col-sm-12">
    <div id="hint8" class="panel-collapse collapse">
	@lang('messages.plant_identification_date_hint')
    </div>
  </div>
</div>
@if(isset($herbaria))
<div class="form-group">
    <label for="herbarium_id" class="col-sm-3 control-label">
@lang('messages.id_herbarium')
</label>
        <a data-toggle="collapse" href="#hint10" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<select name="identification_based_on_herbarium" id="herbarium_id" class="form-control" >
		<option value='' >&nbsp;</option>
	@foreach ($herbaria as $herbarium)
		<option value="{{$herbarium->id}}" {{ '' }}>{{ $herbarium->acronym }} </option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint10" class="panel-collapse collapse">
	@lang('messages.plant_herbarium_id_hint')
    </div>
  </div>
</div>

<div class="form-group herbarium_reference">
    <label for="herbarium_reference" class="col-sm-3 control-label mandatory">
@lang('messages.herbarium_reference')
</label>
	    <div class="col-sm-6">
	<input type="text" name="identification_based_on_herbarium_id" id="herbarium_reference" class="form-control" value="">
      </div>
</div>
@endif



<div class="form-group">
    <label for="identification_notes" class="col-sm-3 control-label">
@lang('messages.identification_notes')
</label>
	    <div class="col-sm-6">
	<textarea name="identification_notes" id="identification_notes" class="form-control"></textarea>
            </div>
</div>
</form>
            <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
        <button class="btn btn-success" name="submit" id='submit_batch_identifications'>
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')
				</button>
			    </div>
			</div>

</div>
</div>
<!-- End of identification form -->





<!-- Registered Plants -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.plants')
                    </div>
                    <div class="panel-body">
{!! $dataTable->table([],true) !!}
                </div>
                </div>
        </div>
    </div>
@endsection

@push ('scripts')
{!! $dataTable->scripts() !!}

<script>

$(document).ready(function() {

$("#taxon_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}", "#taxon_id","@lang('messages.noresults')",
        function() {
            // When the identification clean this
            $('input:radio[name=modifier][value=0]').trigger('click');
            $("#identification_notes").val('');
            $("#herbarium_id option:selected").removeAttr("selected");
            // trigger").val('');
        });
$("#identifier_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#identifier_id","@lang('messages.noresults')");

function setIdentificationFields(vel) {
    var adm = $('#herbarium_id option:selected').val();
    if ("undefined" === typeof adm) {
        return; // nothing to do here...
    }
    switch (adm) {
    case "": // no herbarium
        $(".herbarium_reference").hide(vel);
        break;
    default: // other
        $(".herbarium_reference").show(vel);
    }
}

$("#herbarium_id").change(function() { setIdentificationFields(400); });
// trigger this on page load
setIdentificationFields(0);

// Handle form submission event
$('#batch_identify').on('click', function(e){
    if ($('#batch_identification_panel').is(":visible")) {
      $('#batch_identification_panel').hide();
    } else {
      $('#batch_identification_panel').show();
    }
    /* var table =  $('#dataTableBuilder').DataTable();
    var form = this;
    var rows_selected = table.column( 0 ).checkboxes.selected();
    Iterate over all selected checkboxes
    $('#batch_list').val( rows_selected.join());
    */
});


$('#submit_batch_identifications').on('click',function(e){
    //check if mandatory fields are filled
    var table =  $('#dataTableBuilder').DataTable();
    var rows_selected = table.column( 0 ).checkboxes.selected();

    var taxon = $('#taxon_id').val();
    var identifier = $('#identifier_id').val();
    var adate = $('#identification_date_year').val();
    if (rows_selected.length==0 || taxon==null || identifier==null || adate==0) {
        var txt = "@lang('messages.plants_batch_identify_alert')";
        alert(txt);
    } else {
      $('#batch_list').val( rows_selected.join());
      var txt = rows_selected.length+" @lang('messages.plants_batch_identify_confirm')";
      if (confirm(txt)) {
          $("#batch_identification_form"). submit();
      }
    }
});



});

</script>
@endpush
