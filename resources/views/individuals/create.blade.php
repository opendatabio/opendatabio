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
            @lang('messages.hint_individual_create')
          </div>
        </div>

      </div>

      <div class="panel panel-default">

        <div class="panel-heading">
            @lang('messages.new_individual')
        </div>

        <div class="panel-body">
          <!-- Display Validation Errors -->
          @include('common.errors')


          @if (isset($individual))
    		    <form action="{{ url('individuals/' . $individual->id)}}" method="POST" class="form-horizontal">
              {{ method_field('PUT') }}
            @else
  		    <form action="{{ url('individuals')}}" method="POST" class="form-horizontal">
            @endif

            {{ csrf_field() }}


          <!-- TAG OR NUMBER FOR INDIVIDUAL  -->
          <div class="form-group">
              <label for="tag" class="col-sm-3 control-label mandatory">
                @lang('messages.individual_tag')
              </label>
              <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
          	  <div class="col-sm-6">

          	     <input type="text" name="tag" id="tag" class="form-control" value="{{ old('tag', isset($individual) ? $individual->tag : null) }}">
              </div>
              <div class="col-sm-12">
                <div id="hint1" class="panel-collapse collapse">
                  @lang('messages.hint_individual_tag')
                </div>
              </div>
          </div>


          <!-- collector -->
          <div class="form-group">
              <label for="collectors" class="col-sm-3 control-label mandatory">
                @lang('messages.collector')
              </label>
              <a data-toggle="collapse" href="#hint5" class="btn btn-default">?</a>
          	  <div class="col-sm-6">
                {!! Multiselect::autocomplete('collector',
                  $persons->pluck('abbreviation', 'id'),
                  isset($individual) ? $individual->collectors->pluck('person_id') :
                  (empty(Auth::user()->person_id) ? '' : [Auth::user()->person_id] ),
                  ['class' => 'multiselect form-control'])
                !!}
              </div>
              <div class="col-sm-12">
                <div id="hint5" class="panel-collapse collapse">
          	       @lang('messages.individual_collectors_hint')
                 </div>
               </div>
          </div>


          <!-- DATE INCOMPLETE DATE -->
          <div class="form-group">
            <label for="date" class="col-sm-3 control-label mandatory">
              @lang('messages.tag_date')
            </label>
            <a data-toggle="collapse" href="#hint4" class="btn btn-default">?</a>
            <div class="col-sm-6">
              {!! View::make('common.incompletedate')->with([
                  'object' => isset($individual) ? $individual : null,
                  'field_name' => 'date'
              ]) !!}
            </div>
            <div class="col-sm-12">
              <div id="hint4" class="panel-collapse collapse">
               @lang('messages.individual_date_hint')
              </div>
            </div>
          </div>



<!-- LOCATION BLOCK -->
  <!-- when creating an individual a single location is allowed -->
  <!-- when editing, multiple locations may be added -->
          @php
          $current_location_print = "";
          if (isset($individual)) {
              $current_location_print = $individual->LocationDisplay();
          }
          if (isset($location)) {
              $current_location_print = $location->fullname." ".$location->coordinatesSimple;
          }
          @endphp

          <!-- multiple locations may be added to existing records only -->
          <!-- modal option only when editing an individual or when inserting an individual without coming from a location record -->
          @if (!isset($location))
            <div class="form-group location-show">
                <label for="location_id" class="col-sm-3 control-label mandatory">
                  @lang('messages.location')
                </label>
                <div class="col-sm-6">
                    {!! $current_location_print !!}
                    <!-- only for new individual location records -->
                    <div id='location_show' hidden class='alert-success'></div>
                    <input type="hidden" name="location_id" >
                    <input type="hidden" name="x" >
                    <input type="hidden" name="y" >
                    <input type="hidden" name="angle" >
                    <input type="hidden" name="distance" >
                    <input type="hidden" name="altitude" >
                    <input type="hidden" name="location_date_time" >
                    <input type="hidden" name="location_notes" >

                    <!-- open location modal  -->
                    @if (isset($individual))
                    <br>
                    <button type='button' id='showlocationdatatable' class="btn btn-default btn-sm"><i class="far fa-edit fa-lg"></i></button>
                    @endif
                    <button type="button" class="btn btn-default btn-sm" id="add_location" data-toggle="modal" data-target="#locationModal"><i class='fa fa-plus-square fa-lg'></i></button>



                    <!-- Modal -->
                     <div class="modal fade" id="locationModal" role="dialog">
                       <div class="modal-dialog">
                         <!-- Modal content-->
                         <div class="modal-content">
                           <div class="modal-header">
                             <button type="button" class="close" data-dismiss="modal">&times;</button>
                             <h4 class="modal-title">@lang('messages.location_individual')</h4>
                           </div>
                           <div class="modal-body">
                             {!! View::make('individuals.locationmodal')->with([
                                 'modal_time' => "00:00:00"
                             ]) !!}
                           </div>
                           <div class="modal-footer">
                          <!-- if individual is not set, nor location, then modal return value to form -->
                          @if (!isset($individual))
                              <button type="button" class="btn btn-success save_return" data-dismiss="modal" id='location_return' hidden >@lang('messages.save')</button>
                          @else
                            <!-- if else, editing a record the modal is eitheir adding or editing a location from the datatable list and hence, update is concluded by closing the modal -->
                            <button type="button" class="btn btn-success save_return" id='location_save' hidden >@lang('messages.save')</button>
                          @endif
                            <button type="button" class="btn btn-default" data-dismiss="modal">@lang("messages.close")</button>
                           </div>
                         </div>
                       </div>
                     </div>
                </div>
                @if (isset($individual))

                <!-- this fields is here only to be able to update the summary model -->
                <input type="hidden" name="oldlocation_id" value="{{ $individual->locations->last()->id }}">

                <div class="col-sm-12" id='locationdatatable' >
                    <br><br>
                      {!! $dataTable->table([],true) !!}
                    <br><br>
                </div>
                @endif
            </div>
          @else
            <!-- will enter here only when creating a new individual for a specified location -->
            <!-- location is defined check for x and y if the case -->
            <div class="form-group location-show">
                <label for="location_id" class="col-sm-3 control-label mandatory">
                  @lang('messages.location')
                </label>
                <div class="col-sm-6">
                    {{ $current_location_print }}
                    <input type="hidden" name="location_id" value="{{ old('location_id', $location->id) }}">
                    <input type="hidden" id="location_type" name="location_type" value ="{{old('location_type', $location->adm_level)}}">
                  </div>
            </div>


            <div class="form-group super-relative">
                <label for="relative_position" class="col-sm-3 control-label">@lang('messages.relative_position')</label>
                <a data-toggle="collapse" href="#hint12" class="btn btn-default">?</a>
                 <div class="col-sm-6 super-xy">
              X: <input type="text" name="x" id="x" class="form-control latlongpicker" value="{{ old('x', null) }}">(m)&nbsp;
              Y: <input type="text" name="y" id="y" class="form-control latlongpicker" value="{{ old('y', null) }}">(m)
                </div>
                 <div class="col-sm-6 super-ang">
                    @lang('messages.angle'): <input type="text" name="angle" id="angle" class="form-control latlongpicker" value="{{ old('x', null) }}">&nbsp;
                    @lang('messages.distance'): <input type="text" name="distance" id="distance" class="form-control latlongpicker" value="{{ old('y', null) }}">(m)
                </div>
                <div class="col-sm-12">
                  <div id="hint12" class="panel-collapse collapse">
                     @lang('messages.individual_position_hint')
                   </div>
                 </div>
            </div>

          @endif






<!-- PROJECT -->
<div class="form-group">
    <label for="project" class="col-sm-3 control-label mandatory">
      @lang('messages.project')
    </label>
    <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
    <div class="col-sm-6">
      @if (count($projects))
	       <?php $selected = old('project_id', isset($individual) ? $individual->project_id : (Auth::user()->defaultProject ? Auth::user()->defaultProject->id : null)); ?>
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
	       @lang('messages.individual_project_hint')
       </div>
     </div>
</div>



<!-- NOTES -->
<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
      @lang('messages.notes')
    </label>
	  <div class="col-sm-6">
	     <textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($individual) ? $individual->notes : null) }}</textarea>
    </div>
</div>


<!-- IDENTIFICATION OPTIONS
1. an individual may have its own identification - in this case and Identification object is created for the individual
2. or have an identification that depends from another individual having a voucher in collection - in this case an identification relationship is established with the voucher_id field.
-->

@php
$identification_other = '';
$identification_self = '';
if (empty(old())) { // no "old" value, we're just arriving
    if (isset($individual)) {
      if ($individual->identification_individual_id) {
          $identification_other = 'checked';
      } else {
          $identification_self = 'checked';
      }
   }
} else { // "old" value is available, work with it
  if (!empty(old('identification_selfother'))) {
    if (old('identification_selfother') == 1) {
        $identification_other = 'checked';
    } else {
        $identification_self = 'checked';
    }
  }
}
@endphp
<div class="form-group">
    <div class="col-md-6 col-md-offset-3">
        <div class="radio">
            <label>
                <input type="radio" name="identification_selfother" value=1  {{$identification_other}} >@lang('messages.identification_other')
            </label>
            <label>
                <input type="radio" name="identification_selfother" value=0  {{$identification_self}} >@lang('messages.identification_self')
            </label>
        </div>
    </div>
</div>



<!-- OTHER IDENTIFICATION
Can only exists if individual has no vouchers, otherwise must have own id
-->
<div class="form-group identification_other">
  <label for="taxon_id" class="col-sm-3 control-label mandatory">
    @lang('messages.identification_same_as')
  </label>
  <div class="col-sm-6">
    <input type="text" name="individual_autocomplete" id="individual_autocomplete" class="form-control autocomplete" value="">
    <input type="hidden" name="identification_individual_id" id="identification_individual_id" value="{{ old('identification_individual_id', (isset($individual) and $individual->identification_individual_id) ? $individual->identification_individual_id : null) }}">
  </div>
</div>


<!-- SELF IDENTIFICATION BLOCK --->
<div class="group-together identification_self">
  <div class="form-group identification_self">
    <div class="panel-heading">
      <h4 class='panel-title mandatory'>
        <strong>@lang('messages.identification')</strong>
      </h4>
    </div>
  </div>
  <div class="form-group identification_self">
    <label for="taxon_id" class="col-sm-3 control-label mandatory">
      @lang('messages.taxon')
    </label>
    <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
	  <div class="col-sm-6">
      <input type="text" name="taxon_autocomplete" id="taxon_autocomplete" class="form-control autocomplete"
    value="{{ old('taxon_autocomplete', (isset($individual) and $individual->identification and $individual->identification->taxon) ? $individual->identification->taxon->fullname : null) }}">
      <input type="hidden" name="taxon_id" id="taxon_id"
    value="{{ old('taxon_id', (isset($individual) and $individual->identification and $individual->identification->taxon) ? $individual->identification->taxon_id : null) }}">
    </div>
    <div class="col-sm-12">
      <div id="hint6" class="panel-collapse collapse">
	       @lang('messages.individual_taxon_hint')
       </div>
     </div>
   </div>

   <div class="form-group identification_self">
    <label for="modifier" class="col-sm-3 control-label">
      @lang('messages.modifier')
    </label>
    <a data-toggle="collapse" href="#hint9" class="btn btn-default">?</a>
	  <div class="col-sm-6">
	     <?php $selected = old('modifier', (isset($individual) and $individual->identification) ? $individual->identification->modifier : null); ?>
       @foreach (App\Models\Identification::MODIFIERS as $modifier)
         <span>
    		     <input type = "radio" name="modifier" value="{{$modifier}}" {{ $modifier == $selected ? 'checked' : '' }}>
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
  <div class="form-group identification_self">
    <label for="identifier_id" class="col-sm-3 control-label mandatory">
      @lang('messages.identifier')
    </label>
    <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	  <div class="col-sm-6">
      <input type="text" name="identifier_autocomplete" id="identifier_autocomplete" class="form-control autocomplete"
    value="{{ old('identifier_autocomplete', (isset($individual) and $individual->identification) ? $individual->identification->person->full_name . ' [' . $individual->identification->person->abbreviation . ']'  : (Auth::user()->person ? Auth::user()->person->full_name : null)) }}">
    <input type="hidden" name="identifier_id" id="identifier_id"
    value="{{ old('identifier_id', (isset($individual) and $individual->identification) ? $individual->identification->person_id : Auth::user()->person_id) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint7" class="panel-collapse collapse">
	@lang('messages.individual_identifier_id_hint')
    </div>
  </div>
  </div>

<div class="form-group identification_self">
    <label for="identification_date" class="col-sm-3 control-label mandatory">
@lang('messages.identification_date')
</label>
        <a data-toggle="collapse" href="#hint8" class="btn btn-default">?</a>
	    <div class="col-sm-6">
{!! View::make('common.incompletedate')->with([
    'object' => (isset($individual) and $individual->identification) ? $individual->identification : null,
    'field_name' => 'identification_date'
]) !!}
            </div>
  <div class="col-sm-12">
    <div id="hint8" class="panel-collapse collapse">
	@lang('messages.individual_identification_date_hint')
    </div>
  </div>
</div>
<div class="form-group identification_self">
    <label for="biocollection_id" class="col-sm-3 control-label">
@lang('messages.id_biocollection')
</label>
        <a data-toggle="collapse" href="#hint10" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('biocollection_id', (isset($individual) and $individual->identification) ? $individual->identification->biocollection_id : null); ?>

	<select name="biocollection_id" id="biocollection_id" class="form-control" >
		<option value='' >&nbsp;</option>
	@foreach ($biocollections as $biocollection)
		<option value="{{$biocollection->id}}" {{ $biocollection->id == $selected ? 'selected' : '' }}>
            {{ $biocollection->acronym }}
		</option>
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
	<input type="text" name="biocollection_reference" id="biocollection_reference" class="form-control" value="{{ old('biocollection_reference', (isset($individual) and $individual->identification) ? $individual->identification->biocollection_reference : null) }}">
            </div>
</div>

<div class="form-group identification_self">
    <label for="identification_notes" class="col-sm-3 control-label">
@lang('messages.identification_notes')
</label>
	<div class="col-sm-6">
	<textarea name="identification_notes" id="identification_notes" class="form-control">{{ old('identification_notes', (isset($individual) and $individual->identification) ? $individual->identification->notes : null) }}</textarea>
      </div>
</div>
</div>
<!-- END IDENTIFICATION BLOCK -->



<div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
                <button type="submit" class="btn btn-success" name="submit" value="submit"
@if(!count($projects))
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

</div>
</div>

@endsection

@push ('scripts')

{!! $dataTable->scripts() !!}

<script>

$(document).ready(function() {

  $(".identification_self").hide();
  $('.identification_other').hide();
  $('#locationdatatable').hide();
  $('#showlocationdatatable').on('click',function(){
    if ($('#locationdatatable').is(":hidden")) {
      $('#locationdatatable').show();
    } else {
      $('#locationdatatable').hide();
    }
  });


  $("#location_autocomplete").odbAutocomplete(
        "{{url('locations/autocomplete')}}", "#location_id", "@lang('messages.noresults')", null, undefined,
        function(suggestion) {
            $("#location_type").val(suggestion.adm_level);
            $('.location-extra').show();
            setAngXYFields(0);
            setLocationDate(0);
  });

  $("#individual_autocomplete").odbAutocomplete("{{url('individuals/autocomplete')}}", "#identification_individual_id", "@lang('messages.noresults')");


  $("#taxon_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}", "#taxon_id","@lang('messages.noresults')",
        function() {
            // When the identification of a individual or voucher is changed, all related fields are reset
            $('input:radio[name=modifier][value=0]').trigger('click');
            $("#identifier_id").val('');
            $('#identifier_autocomplete').val('');
            $("#identification_date_year").val((new Date).getFullYear());
            $("#identification_date_month").val((new Date).getMonth());
            $("#identification_date_day").val((new Date).getDay());
            $("#biocollection_id").val('');
            $("#biocollection_reference").val('');
            $("#identification_notes").val('');
  });
  $("#identifier_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#identifier_id", "@lang('messages.noresults')");


  // trigger this on page load
  setIdentificationFields(0);
  setAngXYFields(0);

});


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

$("input[name=identification_selfother]").change(function() {
    var fromvoucher = $('input[name=identification_selfother]:checked').val();
    if (fromvoucher == 1) {
      $('.identification_other').show();
      $('.identification_self').hide();
    } else {
      $('.identification_other').hide();
      $('.identification_self').show();
    }
});



function setAngXYFields(vel) {
  var adm = $('#location_type').val();
  if ("undefined" === typeof adm) {
      return; // nothing to do here...
  }
  switch (adm) {
  case "100": // plot
  case "101": // transect; fallover!
      $(".super-xy").show(vel);
      $(".super-relative").show(vel);
      $(".super-ang").hide(vel);
      break;
  case "999": // point
      $(".super-xy").hide(vel);
      $(".super-relative").show(vel);
      $(".super-ang").show(vel);
      break;
  default: // other
      $(".super-relative").hide(vel);
      break;
  }
}


$("#biocollection_id").change(function() { setIdentificationFields(400); });







/** USED IN THE LOCATION MODAL */
$("#autodetect").click(function(e) {
  $( "#spinner" ).css('display', 'inline-block');
  $(".savedetect").hide();
  $.ajaxSetup({ // sends the cross-forgery token!
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  })
  e.preventDefault(); // does not allow the form to submit
  $.ajax({
    type: "POST",
    url: $('input[name="route-url"]').val(),
    dataType: 'json',
          data: {
            'adm_level': 999,
            'geom': null,
            'lat1': $('input[name="lat1"]').val(),
            'lat2': $('input[name="lat2"]').val(),
            'lat3': $('input[name="lat3"]').val(),
            'latO': $("input[name='latO']:checked").val(),
            'long1': $('input[name="long1"]').val(),
            'long2': $('input[name="long2"]').val(),
            'long3': $('input[name="long3"]').val(),
            'longO': $("input[name='longO']:checked").val(),
            'geom_type': null
          },
    success: function (data) {
      $( "#spinner" ).hide();
      if ("error" in data) {
        $( "#ajax-error" ).collapse("show");
        $( "#ajax-error" ).text(data.error);
      } else {

        // ONLY removes the error if request is success
        var haslocation = data.detectedLocation[0];
        if (haslocation != null) {
          $("#detected_location").text("@lang('messages.location_exists'):  " + data.detectdata[1] );
          $('#locationfield').show();
          $("#location_id").val(data.detectedLocation[0]);
          $("#location_autocomplete").val(data.detectedLocation[1]);
          $("#location_type").val(data.detectedLocation[2]);
          $('#coordinates').hide();
          if ($('.location-extra').is(":hidden")) {
            $('.location-extra').show();
          }
          setAngXYFields(0);
          setLocationDate();
        } else {

          $("#detected_location").text("@lang('messages.location_belongs'):  " + data.detectdata[0] );

          $('#coordinates').hide();
          $(".savedetect").show();

          //create point name to be saved as a new location if confirmed by user
          //var name = data.detectdata[4].replace(/[A-Z\(\)-\.\s]/g,"");
          //if (name.length >12) {
            //name = name.substring(0,11);
          //}
          name = "{{config('app.unnamedPoint_basename')}}" + "_" + "{{ uniqid() }}";
          $("input[name=location_name]").val(name);
          $("input[name=location_parent_id]").val(data.detectdata[1]);
          $("input[name=location_geom]").val(data.detectdata[4]);
          $("input[name=location_uc_id]").val(data.detectdata[3]);
          $("input[name=location_adm_level]").val("{{ App\Models\Location::LEVEL_POINT }}");

        }
        $( "#ajax-error" ).collapse("hide");
      }
    },
    error: function(e){
      $( "#spinner" ).hide();
      $( "#ajax-error" ).collapse("show");
      $( "#ajax-error" ).text('Error sending AJAX request');
    }
  })
});



function setLocationDate() {
  var year = $('#date_year option:selected').val();
  var month = $('#date_month option:selected').val();
  month = String(month).padStart(2, '0');
  var day = $('#date_day option:selected').val();
  day = String(day).padStart(2, '0');
  var date = year + "-" + month + "-" + day;
  var hasdate = $('input[name=modal_date]').val();
  if (day>0 & month>0 & hasdate === "") {
    //alert(hasdate + 'will be here' + date );
    $('input[name=modal_date]').val(date);
  }
}

  $("input[name=latlong]").change(function() {
    $(".savedetect").hide();
    var aslatlong = $('input[name=latlong]:checked').val();
    if (aslatlong == 1) {
      $('#coordinates').show();
      $('#locationfield').hide();
      $('.location-extra').hide();
      $('.super-relative').hide();
      $(".save_return").hide();
    } else {
      $('#coordinates').hide();
      $('#locationfield').show();
      var haslocation = $('input[name=location_id]').val();
      if ($('.location-extra').is(":hidden") & haslocation>0) {
        $('.location-extra').show();
      }
      $(".save_return").show();
      $('#location_type').val(null);
      $('#location_id').val(null);
      $('#location_autocomplete').val(null);
      setAngXYFields(400);
    }
  });

  /** USED IN THE LOCATION MODAL TO SAVE A NEW Location*/
  $("#savedetected").click(function(e) {
    $( "#spinner-save" ).css('display', 'inline-block');
    $.ajaxSetup({ // sends the cross-forgery token!
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
    e.preventDefault(); // does not allow the form to submit
    $.ajax({
      type: "POST",
      url: $('input[name="route-url-save"]').val(),
      dataType: 'json',
            data: {
              'geom': $('input[name="location_geom"]').val(),
              'name': $('input[name="location_name"]').val(),
              'parent_id': $('input[name="location_parent_id"]').val(),
              'adm_level': $('input[name="location_adm_level"]').val(),
              'uc_id': $('input[name="location_uc_id"]').val(),
            },
      success: function (data) {
        $( "#spinner-save" ).hide();
        if ("error" in data) {
          $( "#ajax-error" ).text(data.error);
          $( "#ajax-error" ).show();
        } else {
            if (!$( "#ajax-error" ).is(':hidden')) {
              $( "#ajax-error" ).hide();
            }
            $(".savedetect").hide();
            $('#locationfield').show();
            $("#location_id").val(data.savedLocation[0]);
            $("#location_autocomplete").val(data.savedLocation[1]);
            $("#location_type").val(data.savedLocation[2]);
            $('#coordinates').hide();
            if ($('.location-extra').is(":hidden")) {
              $('.location-extra').show();
            }
            setAngXYFields(0);
            setLocationDate();
            $( "#ajax-error" ).collapse("hide");
            $(".save_return").show();
        }
      },
      error: function(e){
        $( "#spinner-save" ).hide();
        $( "#ajax-error" ).collapse("show");
        $( "#ajax-error" ).text('Error sending AJAX request');
      }
    })
  });


  /* USED WHEN CLOSING THE LOCATION MODAL WHEN CREATING A NEW INDIVIDUAL */
  /* returns values from modal inputs to from inputs */
  $('#location_return').click(function(e) {
      var text = $('input[name=location_autocomplete]').val();
      $('input[name=location_id]').val(
        $('input[name=modal_location_id]').val()
      );
      var x = $('input[name=modal_x]').val();
      if (x) {
        $('input[name=x]').val(x);
        text = text + '<br><strong>X</strong>: ' + x + 'm ';
      }
      var y = $('input[name=modal_y]').val();
      if (y) {
        $('input[name=y]').val(y);
        text = text + '<br><strong>Y</strong>: ' + x + 'm ';
      }
      var angle = $('input[name=modal_angle]').val();
      if (angle) {
        $('input[name=angle]').val(angle);
        text = text + '<br><strong>Angle</strong>: ' + angle + '&#176; ';
      }
      var distance = $('input[name=modal_distance]').val();
      if (distance) {
        $('input[name=distance]').val(distance);
        text = text + '<br><strong>Distance</strong>: ' + distance;
      }
      var altitude = $('input[name=modal_altitude]').val();
      if (altitude) {
        $('input[name=altitude]').val(altitude);
        text = text + '<br><strong>Altitude</strong>: ' + altitude + 'm ';
      }
      var datetime = $('input[name=modal_date]').val() + " " +  $('input[name=modal_time]').val();
      if ($('input[name=modal_date]').val()) {
        $('input[name=location_date_time]').val(datetime);
        text = text + '<br><strong>DateTime</strong>: ' + datetime;
      }
      var locnotes =  $('textarea[name=modal_notes]').val();
      if (locnotes) {
        $('input[name=location_notes]').val(locnotes);
        text = text + '<br><strong>Notes</strong>: ' + locnotes;
      }
      $('#location_show').html(text);
      $('#location_show').show();
  });

  $('#add_location').on('click',function(e) {
    $('input[name=indloc_id]').val(null);
    $("#location_id").val(null);
    $("#location_autocomplete").val(null);
    $("#location_type").val(null);
    $('input[name=modal_x]').val(null);
    $('input[name=modal_y]').val(null);
    $('input[name=modal_angle]').val(null);
    $('input[name=modal_distance]').val(null);
    $('input[name=modal_altitude]').val(null);
    $('textarea[name=modal_notes]').val(null);
    $('input[name=modal_date]').val(null);
    $('input[name=modal_time]').val("00:00:00");
    $("#latlong_1").prop("checked", true);
  });

  //when editing a location get old values and fill modal
  $('#dataTableBuilder').on( 'click', '.editlocation', function (e) {
      var indloc = $(this).data('indloc');
      e.preventDefault(); // does not allow the form to submit
      $.ajax({
        type: "GET",
        url: "{{ route('getIndividualLocation') }}",
        dataType: 'json',
        data: {
          'id': indloc
        },
        success: function (data) {
          $('input[name=indloc_id]').val(data.indLocation[0]);
          $("#location_id").val(data.indLocation[1]);
          $("#location_autocomplete").val(data.indLocation[2]);
          $("#location_type").val(data.indLocation[3]);
          $('input[name=modal_x]').val(data.indLocation[4]);
          $('input[name=modal_y]').val(data.indLocation[5]);
          $('input[name=modal_angle]').val(data.indLocation[6]);
          $('input[name=modal_distance]').val(data.indLocation[7]);
          $('input[name=modal_altitude]').val(data.indLocation[8]);
          $('textarea[name=modal_notes]').val(data.indLocation[9]);
          $('input[name=modal_date]').val(data.indLocation[10]);
          $('input[name=modal_time]').val(data.indLocation[11]);
          $('#location_type_selector').hide();
          setAngXYFields(0);
          $('.location-extra').show();
        },
        error: function(e){
          alert( indloc + " will be error" );
        }
      });
  });




  //if editing or inserting on a new record, save location
  $('#location_save').on('click',function(e) {
    $.ajaxSetup({ // sends the cross-forgery token!
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
    e.preventDefault(); // does not allow the form to submit
    $.ajax({
      type: "POST",
      url: "{{ route('saveIndividualLocation') }}",
      dataType: 'json',
      data: {
        'individual_id': "{{ isset($individual) ? $individual->id : null }}",
        'id': $('input[name=indloc_id]').val(),
        'location_id': $('input[name=modal_location_id]').val(),
        'x': $('input[name=modal_x]').val(),
        'y': $('input[name=modal_y]').val(),
        'angle': $('input[name=modal_angle]').val(),
        'distance': $('input[name=modal_distance]').val(),
        'altitude': $('input[name=modal_altitude]').val(),
        'notes': $('textarea[name=modal_notes]').val(),
        'date_time': $('input[name=modal_date]').val() + " " + $('input[name=modal_time]').val(),
      },
      success: function (data) {
        alert(data.saved);
        $('#dataTableBuilder').DataTable().ajax.reload();
        $("#locationModal").modal('hide');
      },
      error: function(e){
        alert('error');
      }
    });
  });


  //when deleting a location
  $('#dataTableBuilder').on( 'click', '.deletelocation', function (e) {
      var indloc = $(this).data('indloc');
      e.preventDefault(); // does not allow the form to submit
      $.ajax({
        type: "GET",
        url: "{{ route('deleteIndividualLocation') }}",
        dataType: 'json',
        data: {
          'individual_id': "{{ isset($individual) ? $individual->id : null }}",
          'id': indloc
        },
        success: function (data) {
          alert(data.deleted);
          $('#dataTableBuilder').DataTable().ajax.reload();
        },
        error: function(e){
          alert( indloc + " will be error" );
        }
      });
  });

</script>

{!! Multiselect::scripts('collector', url('persons/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}

@endpush
