<!-- LOCATION EDITOR -->
<input type="hidden" id="location_type" name="location_type" value ="{{ isset($location_type) ? $location_type : null }}">

<!-- if editing and individual_location this will be filled by jquery -->
<input type="hidden" name="indloc_id" >

<!-- Coordinates if new location to facilitate input -->
@if(!isset($location_type) or $location_type==null)
<div class="form-group" id='location_type_selector'>
    <div class="col-md-6 col-md-offset-3">
        <div class="radio">
            <label>
                <input type="radio" name="latlong" value=0 checked id='latlong_1'>@lang('messages.location_select')
            </label>
            <label>
                <input type="radio" name="latlong" value=1   >@lang('messages.location_latlong_insert')
            </label>
        </div>
    </div>
</div>
@endif


<div id="coordinates" hidden>
  <div class="form-group">
    <label for="lat1" class="col-sm-3 control-label mandatory">
      @lang('messages.latitude')
    </label>
    <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <input type="text" name="lat1" id="lat1" class="form-control latlongpicker" value="{{ old('lat1') }}"><span style="font-size: 200%">&#176;</span>
    	<input type="text" name="lat2" id="lat2" class="form-control latlongpicker" value="{{ old('lat2') }}"><span style="font-size: 200%">'</span>
    	<input type="text" name="lat3" id="lat3" class="form-control latlongpicker" value="{{ old('lat3') }}"><span style="font-size: 200%">"</span>
    	<input type="radio" name="latO" value="1" @if (old('latO',1)) checked @endif > N
        &nbsp;
    	<input type="radio" name="latO" value="0" @if (!old('latO',1)) checked @endif > S
    </div>
  </div>
  <div class="form-group">
    <label for="long1" class="col-sm-3 control-label mandatory">
      @lang('messages.longitude')
    </label>
    <div class="col-sm-6">
    	<input type="text" name="long1" id="long1" class="form-control latlongpicker" value="{{ old('long1') }}"><span style="font-size: 200%">&#176;</span>
    	<input type="text" name="long2" id="long2" class="form-control latlongpicker" value="{{ old('long2') }}"><span style="font-size: 200%">'</span>
    	<input type="text" name="long3" id="long3" class="form-control latlongpicker" value="{{ old('long3') }}"><span style="font-size: 200%">"</span>
    	<input type="radio" name="longO" value="1" @if (old('longO',1)) checked @endif > E
    &nbsp;
    	<input type="radio" name="longO" value="0" @if (!old('longO',1)) checked @endif > W
    </div>
    <div class="col-sm-12">
      <div id="hint6" class="panel-collapse collapse">
         @lang('messages.latlong_hint')
      </div>
    </div>
  </div>

  <!-- detect from lat and long and return parent and location_id if exists -->
  <div class="form-group autodetect">
    <div class="col-sm-offset-3 col-sm-6">
      <input type="hidden" name="route-url" value="{{ route('autodetect') }}">
      <button type="button" class="btn btn-primary" id="autodetect">
        <i class="fa fa-btn fa-plus"></i>
        @lang('messages.autodetect')
      </button>
      <div class="spinner" id="spinner"> </div>
    </div>
  </div>

</div>

<div id="ajax-error" class="collapse alert alert-danger">
  @lang('messages.whoops')
</div>


<!-- if location is new show and fill this after parent detection -->
<div class="form-group savedetect" hidden>
  <div class="col-sm-12 alert alert-success" id='detected_location'> </div>
  <div class="col-sm-offset-3 col-sm-6">
     <input type="hidden" name="location_geom" value="">
     <input type="hidden" name="location_name" value="">
     <input type="hidden" name="location_parent_id" value="">
     <input type="hidden" name="location_adm_level" value="">
     <input type="hidden" name="location_uc_id" value="">
     <input type="hidden" name="route-url-save" value="{{ route('saveForIndividual') }}">
    <button type="button" class="btn btn-success" id="savedetected">
      >>
      @lang('messages.next')
    </button>
    <div class="spinner" id="spinner_save"> </div>
  </div>
</div>


<div id="locationfield" class="form-group">
  <label for="modal_location_id" class="col-sm-3 control-label mandatory">
    @lang('messages.location')
  </label>
  <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
   <div class="col-sm-6">
     <input type="text" name="location_autocomplete" id="location_autocomplete" class="form-control autocomplete" value="{{ old('location_autocomplete', isset($modal_location_name) ? $modal_location_name : null) }}">
     <input type="hidden" name="modal_location_id" id="location_id" value="{{ old('modal_location_id',isset($modal_location_id) ? $modal_location_id : null) }}">
   </div>
   <div class="col-sm-12">
     <div id="hint2" class="panel-collapse collapse">
       @lang('messages.individual_location_hint')
     </div>
   </div>
</div>

<div class="form-group super-relative" hidden>
    <label for="relative_position" class="col-sm-3 control-label">@lang('messages.relative_position')</label>
    <a data-toggle="collapse" href="#hint12" class="btn btn-default">?</a>
     <div class="col-sm-6 super-xy">
  X: <input type="text" name="modal_x" id="x" class="form-control latlongpicker" value="{{ old('modal_x', isset($modal_x) ? $modal_x : null) }}">(m)&nbsp;
  Y: <input type="text" name="modal_y" id="y" class="form-control latlongpicker" value="{{ old('modal_y', isset($modal_y) ? $modal_y : null) }}">(m)
    </div>
     <div class="col-sm-6 super-ang">
        @lang('messages.angle'): <input type="text" name="modal_angle" id="angle" class="form-control latlongpicker" value="{{ old('modal_angle', isset($modal_angle) ? $modal_angle : null) }}">&nbsp;
        @lang('messages.distance'): <input type="text" name="modal_distance" id="distance" class="form-control latlongpicker" value="{{ old('modal_distance', isset($modal_distance) ? $modal_distance : null) }}">(m)
    </div>
    <div class="col-sm-12">
      <div id="hint12" class="panel-collapse collapse">
         @lang('messages.individual_position_hint')
       </div>
     </div>
</div>


<div class="form-group location-extra" hidden>
  <label for="modal_altitude" class="col-sm-3 control-label">@lang('messages.altitude')</label>
  <div class="col-sm-6">
    <input type="text" name="modal_altitude" value="{{ old('modal_altitude',  isset($modal_altitude) ? $modal_altitude : null) }}" >
  </div>
</div>

<div class="form-group location-extra" hidden>
  <label for="modal_date" class="col-sm-3 control-label">@lang('messages.datetime')</label>
  <div class="col-sm-6">
    <input type="date" name="modal_date" value="{{ old('location_date',isset($modal_date) ? $modal_date : null) }}" >
    <input type="time" name="modal_time" value="{{ old('location_time', isset($modal_time) ? $modal_time : null) }}" >
  </div>
</div>

<!-- NOTES -->
<div class="form-group location-extra">
    <label for="modal_notes" class="col-sm-3 control-label">
      @lang('messages.notes')
    </label>
	  <div class="col-sm-6">
	     <textarea name="modal_notes" class="form-control">{{ old('modal_notes',  isset($modal_notes) ? $modal_notes : null)  }}</textarea>
    </div>
</div>
