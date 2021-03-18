@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-offset-2 col-sm-8">

@can ('create', App\Individual::class)
        <div class="panel panel-default">
            <div class="panel-heading">
              @lang('messages.create_individual')
            </div>

            <div class="panel-body">
            <div class="col-sm-6">
            @if (isset($object) and class_basename($object) == 'Location') <!-- we're inside a Location, Project or Taxon view -->
              <a href="{{url ('locations/' . $object->id . '/individuals/create')}}" class="btn btn-success">
            @else
              <a href="{{url ('individuals/create')}}" class="btn btn-success">
            @endif
                <i class="fa fa-btn fa-plus"></i>
                @lang('messages.create')
            </a>
            &nbsp;&nbsp;
            <button class="btn btn-primary" id='batch_identify'>
              @lang('messages.individuals_batch_identify')
            </button>
          </div>
          </div>
        </div>
@endcan


<!--- Batch Identification of individuals hidden only registered users will see and project collaborators will be able to use-->
<?php // TODO: Perhaps make only collaborators of any project having individuals to see it. ?>
<div class="panel panel-default" id='batch_identification_panel' hidden>
  <div class="panel-heading">
    @lang('messages.individuals_batch_identify')
    &nbsp;&nbsp;<a data-toggle="collapse" href="#hint_batch_identify" class="btn btn-default">?</a>
    <div id="hint_batch_identify" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.individuals_batch_identify_hint')
      </div>
  </div>
</div>

  <div class="panel-body">

<form action="{{url('individuals/batchidentify')}}" method="POST" class="form-horizontal" id='batch_identification_form'>
  <!-- csrf protection -->
  {{ csrf_field() }}
<input type='hidden' name='individualids_list' id='batch_list' value='' value="">
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
	@lang('messages.individual_taxon_hint')
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
            @lang('levels.modifier.' . $modifier)
		    </span>
	 @endforeach
            </div>
  <div class="col-sm-12">
    <div id="hint9" class="panel-collapse collapse">
	@lang('messages.individual_modifier_hint')
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
	@lang('messages.individual_identifier_id_hint')
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
	@lang('messages.individual_identification_date_hint')
    </div>
  </div>
</div>

@if(isset($biocollections))
<div class="form-group">
    <label for="biocollection_id" class="col-sm-3 control-label">
@lang('messages.id_biocollection')
</label>
        <a data-toggle="collapse" href="#hint10" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<select name="identification_based_on_biocollection" id="biocollection_id" class="form-control" >
		<option value='' >&nbsp;</option>
	@foreach ($biocollections as $biocollection)
		<option value="{{$biocollection->id}}" {{ '' }}>{{ $biocollection->acronym }} </option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint10" class="panel-collapse collapse">
	@lang('messages.individual_biocollection_id_hint')
    </div>
  </div>
</div>

<div class="form-group biocollection_reference">
    <label for="biocollection_reference" class="col-sm-3 control-label mandatory">
@lang('messages.biocollection_reference')
</label>
	    <div class="col-sm-6">
	<input type="text" name="identification_based_on_biocollection_id" id="biocollection_reference" class="form-control" value="">
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

<div class="form-group">
    <label for="update_nonself_identification" class="col-sm-3 control-label mandatory">
      @lang('messages.individual_update_nonself_identification')
    </label>
        <a data-toggle="collapse" href="#update_nonself_identification" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="checkbox" name="update_nonself_identification" value=1 >
    </div>
  <div class="col-sm-12">
    <div id="update_nonself_identification" class="panel-collapse collapse">
	@lang('messages.individual_update_nonself_identification_hint')
    </div>
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





<!-- Registered individuals -->
          <div class="panel panel-default">
              <div class="panel-heading">
                @if (isset($object)) <!-- we're inside a Location, Project or Taxon view -->
                    @lang('messages.individuals_list_for')<strong> {{ class_basename($object) }}</strong>
                    {!! $object->rawLink() !!}
                    @if (isset($object_second))
                    &nbsp;>>&nbsp;<strong>{{class_basename($object_second )}}</strong> {!! $object_second->rawLink(true) !!}
                    @endif
                    <a href="#" id="about_list" class="btn btn-default"><i class="fas fa-question"></i></a>
                    <div id='about_list_text' ></div>
                @else
                  @lang('messages.individuals')
                  &nbsp;&nbsp;
                  <a data-toggle="collapse" href="#help" class="btn btn-default">
                    @lang('messages.help')
                  </a>
                @endif
              </div>
              <div id="help" class="panel-body collapse">
                @lang('messages.individuals_hint')
                <code>@lang('messages.download_login')</code>

              </div>
              @if (Auth::user())
                {!! View::make('common.exportdata')->with([
                      'object' => isset($object) ? $object : null,
                      'object_second' => isset($object_second) ? $object_second : null,
                      'export_what' => 'Individual'
                ]) !!}
              @else
                <br>
              @endif

            <div class="panel-body responsive">
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

$("#about_list").on('click',function(){
  if ($('#about_list_text').is(':empty')){
    var records = $('#dataTableBuilder').DataTable().ajax.json().recordsTotal;
    if (records == 0) {
      $('#about_list_text').html("@lang('messages.no_permission_list')");
    } else {
      $('#about_list_text').html("@lang('messages.individual_object_list')");
    }
  } else {
    $('#about_list_text').html(null);
  }
});

$("#taxon_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}", "#taxon_id","@lang('messages.noresults')",
        function() {
            // When the identification clean this
            $('input:radio[name=modifier][value=0]').trigger('click');
            $("#identification_notes").val('');
            $("#biocollection_id option:selected").removeAttr("selected");
            // trigger").val('');
        });
$("#identifier_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#identifier_id","@lang('messages.noresults')");

function setIdentificationFields(vel) {
    var adm = $('#biocollection_id option:selected').val();
    if ("undefined" === typeof adm) {
        return; // nothing to do here...
    }
    switch (adm) {
    case "": // no biocollection
        $(".biocollection_reference").hide(vel);
        break;
    default: // other
        $(".biocollection_reference").show(vel);
    }
}

$("#biocollection_id").change(function() { setIdentificationFields(400); });
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
        var txt = "@lang('messages.individuals_batch_identify_alert')";
        alert(txt);
    } else {
      $('#batch_list').val( rows_selected.join());
      var txt = rows_selected.length+" @lang('messages.individuals_batch_identify_confirm')";
      if (confirm(txt)) {
          $("#batch_identification_form"). submit();
      }
    }
});

// Handle form submission event
$('#exports').on('click', function(e){
    if ($('#export_pannel').is(":visible")) {
      $('#export_pannel').hide();
    } else {
      $('#export_pannel').show();
    }
    /* var table =  $('#dataTableBuilder').DataTable();
    var form = this;
    var rows_selected = table.column( 0 ).checkboxes.selected();
    Iterate over all selected checkboxes
    $('#batch_list').val( rows_selected.join());
    */
});
$('#export_sumbit').on('click',function(e){
  var table =  $('#dataTableBuilder').DataTable();
  var rows_selected = table.column( 0 ).checkboxes.selected();
  $('#export_ids').val( rows_selected.join());
  $("#export_form"). submit();
});


});




</script>
@endpush
