@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.new_location')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')
<div id="ajax-error" class="collapse alert alert-danger">
@lang('messages.whoops')
</div>

@if (isset($location))
		    <form action="{{ url('locations/' . $location->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('locations')}}" method="POST" class="form-horizontal">
@endif

                     {{ csrf_field() }}
<div class="form-group">
    <label for="name" class="col-sm-3 control-label mandatory">
@lang('messages.location_name')
</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($location) ? $location->name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="adm_level" class="col-sm-3 control-label mandatory">
@lang('messages.adm_level')
</label>
        <a data-toggle="collapse" href="#hint5" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('adm_level', isset($location) ? $location->adm_level : null); ?>

	<select name="adm_level" id="adm_level" class="form-control" >
	@foreach (App\Models\Location::AdmLevels() as $level)
		<option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
			@lang ('levels.adm_level.' . $level )
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint5" class="panel-collapse collapse">
	@lang('messages.location_adm_level_hint')
    </div>
  </div>
</div>

<div class="form-group super-button">
    <div class="col-sm-6 col-sm-offset-3">
    <input type="hidden" name="geom_type" id="geom_type" value="{{old('geom_type', isset($location) ? $location->geomType : 'point') }}">
    <a href="#" id="toggle_geom" class="btn btn-primary">@lang ('messages.inform_geometry')</a>
    </div>
</div>

<div id="super-geometry">
<div class="form-group">
    <label for="geom" class="col-sm-3 control-label mandatory">
@lang('messages.geometry')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<textarea name="geom" id="geom" class="form-control">{{ old('geom', isset($location) ? $location->geom : null) }}</textarea>
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.geom_hint')
    </div>
  </div>
</div>
</div>


<div id="super-points">
<div class="form-group">
    <label for="lat1" class="col-sm-3 control-label mandatory">
@lang('messages.latitude')
</label>
        <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
<div class="col-sm-7">
	<input type="text" name="lat1" id="lat1" class="form-control latlongpicker" value="{{ old('lat1', isset($location) ? $location->lat1 : null) }}"><span style="font-size: 200%">&#176;</span>
	<input type="text"  name="lat2" id="lat2" class="form-control latlongpicker" value="{{ old('lat2', isset($location) ? $location->lat2 : null) }}"><span style="font-size: 200%">'</span>
	<input type="text"  name="lat3" id="lat3" class="form-control latlongpicker" value="{{ old('lat3', isset($location) ? $location->lat3 : null) }}"><span style="font-size: 200%">"</span>
	<input type="radio" name="latO" value="1" @if ( old('latO', isset($location) ? $location->latO : 1)) checked @endif >&nbsp;N
&nbsp;<input type="radio" name="latO" value="0" @if ( !old('latO', isset($location) ? $location->latO : 1)) checked @endif >&nbsp;S
  </div>
</div>
<div class="form-group" style='resize'>
    <label for="long1" class="col-sm-3 control-label mandatory">
@lang('messages.longitude')
</label>
	    <div class="col-sm-7">
  <input type="text"    name="long1" id="long1" class="form-control latlongpicker" value="{{ old('long1', isset($location) ? $location->long1 : null) }}"><span style="font-size: 200%">&#176;</span>
	<input type="text"    name="long2" id="long2" class="form-control latlongpicker" value="{{ old('long2', isset($location) ? $location->long2 : null) }}"><span style="font-size: 200%">'</span>
	<input type="text"   name="long3" id="long3" class="form-control latlongpicker" value="{{ old('long3', isset($location) ? $location->long3 : null) }}"><span style="font-size: 200%">"</span>
	<input type="radio" name="longO" value="1" @if (old('longO', isset($location) ? $location->longO : 1)) checked @endif >&nbsp;E
&nbsp;<input type="radio" name="longO" value="0" @if (!old('longO', isset($location) ? $location->longO : 1)) checked @endif >&nbsp;W
</div>
  <div class="col-sm-12">
    <div id="hint6" class="panel-collapse collapse">
	@lang('messages.latlong_hint')
    </div>
  </div>
</div>
</div>

<!-- PLOT OR TRANSECTS  -->
<div class="form-group" id="angle-box">
    <label for="x" class="col-sm-3 control-label mandatory">
      @lang('messages.plot_transect_azimuth')
    </label>
    <a data-toggle="collapse" href="#plot_transect_azimuth_hint" class="btn btn-default">?</a>
	  <div class="col-sm-6">
      <input type="text"  name="angle" id="angle" class="form-control latlongpicker" value="{{ old('angle', 360) }}"><span style="font-size: 250%">&#176;</span>
    </div>
  <div class="col-sm-12">
    <div id="plot_transect_azimuth_hint" class="panel-collapse collapse">
	@lang('messages.plot_transect_azimuth_hint')
    </div>
  </div>
</div>


<!-- PLOT OR SUBPLOT DIMENSIONS -->
<div id="super-x">
<div class="form-group">
    <label for="x" class="col-sm-3 control-label mandatory">
      @lang('messages.dimensions')
    </label>
    <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	  <div class="col-sm-6">
      <span id='xlabel'>X</span>:&nbsp;<input type="text"  name="x" id="x" class="form-control latlongpicker" value="{{ old('x', isset($location) ? $location->x : null) }}">&nbsp;m&nbsp;
      <span id='ylabel'>Y</span>:&nbsp;<input type="text"  name="y" id="y" class="form-control latlongpicker" value="{{ old('y', isset($location) ? $location->y : null) }}">&nbsp;m
    </div>
  <div class="col-sm-12">
    <div id="hint7" class="panel-collapse collapse">
	@lang('messages.dimensions_hint')
    </div>
  </div>
</div>
</div>



<div class="form-group autodetect">
    <div class="col-sm-offset-3 col-sm-6">
      <input type="hidden" name="route-url" value="{{ route('autodetect') }}">
      <button type="submit" class="btn btn-primary" id="autodetect">
          <i class="fa fa-btn fa-plus"></i>@lang('messages.autodetect')
      </button>
      <div class="spinner" id="spinner"> </div>
    </div>
</div>


<div class="form-group parent_id">
    <label for="parent_id" class="col-sm-3 control-label mandatory">
      @lang('messages.location_parent')
    </label>
    <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	   <div class="col-sm-6">
        <input type="text" name="parent_autocomplete" id="parent_autocomplete" class="form-control autocomplete"
        value="{{ old('parent_autocomplete', (isset($location) and $location->parent) ? $location->parent->fullname : null) }}">
        <input type="hidden" name="parent_id" id="parent_id"
        value="{{ old('parent_id', isset($location) ? $location->parent_id : null) }}">
        <input type="hidden" name="parent_type" id="parent_type" value="{{old('parent_type', (isset($location) and $location->parent) ? $location->parent->adm_level : '')}}">
      </div>
    <div class="col-sm-12">
      <div id="hint2" class="panel-collapse collapse">
  	   @lang('messages.location_parent_hint')
      </div>
    </div>
</div>

<!--
<div class="form-group parent_id">
      <label for="ismarine" class="col-sm-3 control-label">
        @lang('messages.location_ismarine')
      </label>
      <a data-toggle="collapse" href="#ismarine_hint" class="btn btn-default">?</a>
      <div class="col-sm-6">
      <input type="checkbox" name="ismarine" id="ismarine" value="1">
     </div>
      <div id="ismarine_hint" class="col-sm-12 panel-collapse collapse">
        @lang('messages.location_ismarine_hint')
      </div>
</div>
-->

<div class="form-group super-start">
    <label for="startx" class="col-sm-3 control-label mandatory">
@lang('messages.start')
</label>
	    <div class="col-sm-6">
	Start-X: <input type="text" name="startx" id="startx" class="form-control latlongpicker" value="{{ old('startx', isset($location) ? $location->startx : null) }}">(m)&nbsp;
	Start-Y: <input type="text" name="starty" id="starty" class="form-control latlongpicker" value="{{ old('starty', isset($location) ? $location->starty : null) }}">(m)
        </div>
</div>



<!-- collector -->
<div class="form-group" id="super-uc">
    <label for="related_locations" class="col-sm-3 control-label">
      @lang('messages.other_parents')
    </label>
    <a data-toggle="collapse" href="#other_parents" class="btn btn-default">?</a>
	  <div class="col-sm-6">
      {!! Multiselect::autocomplete('related_locations',
        $related_locations->pluck('name', 'id'),
        isset($location) ? $location->relatedLocations->pluck('related_id') :
        '',
        ['class' => 'multiselect form-control'])
      !!}
    </div>
    <div class="col-sm-12">
      <div id="other_parents" class="panel-collapse collapse">
	       @lang('messages.other_parent_location_hint')
       </div>
     </div>
</div>



<div class="form-group">
    <label for="altitude" class="col-sm-3 control-label">
@lang('messages.altitude')
</label>
	    <div class="col-sm-6">
	<input type="text" name="altitude" id="altitude" class="form-control" value="{{ old('altitude', isset($location) ? $location->altitude : null) }}">
    </div>
</div>
<div class="form-group">
        <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
    <label for="datum" class="col-sm-3 control-label">
@lang('messages.datum')
</label>
	    <div class="col-sm-6">
	<input type="text" name="datum" id="datum" class="form-control" value="{{ old('datum', isset($location) ? $location->datum : 'WGS84') }}">
    </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.datum_hint')
    </div>
  </div>
</div>
<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($location) ? $location->notes : null) }}</textarea>
    </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
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
			@if (isset($location))
			@can ('delete', $location)
		    <form action="{{ url('locations/'.$location->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('DELETE') }}
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.remove_location')

				</button>
			    </div>
			</div>
		    </form>
		    @endcan <!-- end can delete -->
		    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
@push ('scripts')
<script>
$(document).ready(function() {
    $("#parent_autocomplete").odbAutocomplete(
        "{{url('locations/autocomplete')}}","#parent_id", "@lang('messages.noresults')", undefined, {'scope':  'exceptucs'},
        function (suggestion) {
            $("#parent_type").val(suggestion.adm_level);
            setLocationFields(400);
        });
    /*
    $("#uc_autocomplete").odbAutocomplete("{{url('locations/autocomplete')}}","#uc_id", "@lang('messages.noresults')", undefined, {'scope':  'ucs'});
    */
});

  function toogleGeometryLatLong(vel)  {
      var adm = $('#adm_level option:selected').val();
      var geomtype = $("#geom_type").val();
      switch(geomtype) {
        case "point":
          $("#geom_type").val('nonpoint');
          $("#super-geometry").show(vel);
          $("#super-points").hide(vel);
          $("#toggle_geom").html("Inform coordinates");
          $("#angle-box").hide(vel);
          break;
      default:
          if (adm=="100" | adm=="101") {
            $("#angle-box").show(vel);
          }
          $("#geom_type").val('point');
          $("#super-geometry").hide(vel);
          $("#super-points").show(vel);
          $("#toggle_geom").html("Inform geometry");
      }
  }


	/** For Location create and edit pages. The available fields change with changes on adm_level.
	 * The "vel" parameter determines the velocity in which the animation is made. **/
	function setLocationFields(vel) {
		var adm = $('#adm_level option:selected').val();
    var geomtype = $('#geom_type').val();
    var parent_type = $('#parent_type').val();
		if ("undefined" === typeof adm) {
			return; // nothing to do here...
		}
		switch (adm) {
			case "999": // point
          switch(geomtype) {
          case "point":
              $("#super-geometry").hide(vel);
              $("#super-points").show(vel);
              break;
          default:
              $("#super-geometry").show(vel);
              $("#super-points").hide(vel);
          }
          $("#angle-box").hide(vel);
  				$("#super-x").hide(vel);
  				$("#super-uc").show(vel);
          $(".parent_id").show(vel);
          $(".autodetect").show(vel);
          $(".super-button").show(vel);
          $(".super-start").hide(vel);
				break;
			case "100": // plot
            switch(geomtype) {
            case "point":
                $("#super-geometry").hide(vel);
                $("#super-points").show(vel);
                $("#angle-box").show(vel);
                break;
            default:
                $("#super-geometry").show(vel);
                $("#super-points").hide(vel);
                $("#angle-box").hide(vel);
            }
            $(".super-button").show(vel);
    				$("#super-x").show(vel);
    				$("#super-uc").show(vel);
            $(".parent_id").show(vel);
            $(".autodetect").show(vel);
            //$("#super-buffer").hide(vel);

            //in case the location is a subplot
            // start-x and start-y ONLY here
            if (adm == 100 && parent_type == 100) {
                $(".super-start").show(vel);
            } else {
                $(".super-start").hide(vel);
            }
            $("#xlabel").html("X");
            $("#ylabel").html("Y");
            break;
      case "101": // transect, fall through
            switch(geomtype) {
              case "point":
                $("#super-geometry").hide(vel);
                $("#super-points").show(vel);
                $("#angle-box").show(vel);
                break;
              default:
                $("#super-geometry").show(vel);
                $("#super-points").hide(vel);
                $("#angle-box").hide(vel);
            }
            $(".super-button").show(vel);
            $("#super-x").show(vel);
            //$("#super-buffer").show(vel);
        		$("#super-uc").show(vel);
            $(".parent_id").show(vel);
            $(".autodetect").show(vel);
            $(".super-start").hide(vel);
            $("#xlabel").html("@lang('messages.transect_length')");
            $("#ylabel").html("@lang('messages.transect_buffer')");


            break;
      case "2": // country
    				$("#super-geometry").show(vel);
    				$("#super-points").hide(vel);
    				$("#super-x").hide(vel);
    				$("#super-uc").hide(vel);
            $(".parent_id").hide(vel);
            $(".autodetect").hide(vel);
            $(".super-button").hide(vel);
            $(".super-start").hide(vel);
            $("#angle-box").hide(vel);
            //$("#super-buffer").hide(vel);

            break;
	    default: // other
    				$("#super-geometry").show(vel);
    				$("#super-points").hide(vel);
    				$("#super-x").hide(vel);
    				$("#super-uc").hide(vel);
            $(".parent_id").show(vel);
            $(".autodetect").show(vel);
            $(".super-button").hide(vel);
            $(".super-start").hide(vel);
            $("#super-buffer").hide(vel);
            $("#angle-box").hide(vel);
      }

  }
	$("#adm_level").change(function() { setLocationFields(400); });
	$("#toggle_geom").click(function() { toogleGeometryLatLong(400); });
    // trigger this on page load
	setLocationFields(0);
  $( function() {
      $( ".form-control.latlongpicker" ).resizable();
  } );

	/** Ajax handling for autodetecting location */
	$("#autodetect").click(function(e) {
		$( "#spinner" ).css('display', 'inline-block');
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
                'adm_level': $('#adm_level option:selected').val(),
                'geom': $('#geom').val(),
                'lat1': $('input[name="lat1"]').val(),
                'lat2': $('input[name="lat2"]').val(),
                'lat3': $('input[name="lat3"]').val(),
                'latO': $("input[name='latO']:checked").val(),
                'long1': $('input[name="long1"]').val(),
                'long2': $('input[name="long2"]').val(),
                'long3': $('input[name="long3"]').val(),
                'longO': $("input[name='longO']:checked").val(),
                'geom_type': $('#geom_type').val()
            },
			success: function (data) {
				$( "#spinner" ).hide();
				if ("error" in data) {
					$( "#ajax-error" ).collapse("show");
					$( "#ajax-error" ).text(data.error);
				} else {
					// ONLY removes the error if request is success
					$( "#ajax-error" ).collapse("hide");
					$("#parent_autocomplete").val(data.detectdata[0]);
					$("#parent_id").val(data.detectdata[1]);
          //alert(data.detectrelated[0]['id']);
          //alert(data.detectrelated[0]['name']);
          var related = data.detectrelated;
          if (related) {
            let text = "";
            for (let i = 0; i < related.length; i++) {
              text += '<span class="multiselector" onclick="$(this).remove();" ><input type="hidden" name="related_locations[]" value="'+related[i]['id']+'">'+related[i]['name']+'</span>';
            }
            $("#related_locations-span").html(text);
          } else {
            $("#related_locations-span").html("");
          }
					//$("#uc_autocomplete").val(data.detectdata[2]);
					//$("#uc_id").val(data.detectdata[3]);
				}
			},
			error: function(e){
				$( "#spinner" ).hide();
				$( "#ajax-error" ).collapse("show");
				$( "#ajax-error" ).text('Error sending AJAX request');
			}
		})
	});
</script>

{!! Multiselect::scripts('related_locations', url('locations/autocomplete-related'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}

@endpush
