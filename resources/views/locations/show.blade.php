@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.location')
                </div>

                <div class="panel-body">
		    <strong>
@lang('messages.location_name')
:</strong> {{ $location->name }}
<br>
		    <strong>
@lang('messages.adm_level')
:</strong> @lang ('levels.adm.' . $location->adm_level) 
<br>
@if ($location->altitude)
<strong>
@lang('messages.altitude')
:</strong> {{ $location->altitude }}
<br>
@endif
<!-- X, Y, etc) -->
@if ($location->x or $location->y)
<strong>
@lang('messages.dimensions')
:</strong> X: {{ $location->x }}m, Y: {{$location->y}}m</a>
<br>
@endif
@if ($location->startx or $location->starty)
<strong>
@lang('messages.position')
:</strong> X: {{ $location->startx }}m, Y: {{$location->starty}}m</a>
<br>
@endif

@if ($location->datum)
<strong>
@lang('messages.datum')
:</strong> {{ $location->datum }}
<br>
@endif
@if ($location->uc_id)
<strong>
@lang('messages.uc')
:</strong> <a href="{{url('locations/'. $location->uc->id)}}">{{ $location->uc->name }}</a>
<br>
@endif
<strong> 
@lang('messages.total_descendants')
:</strong> {{ $location->getDescendants()->count() }}
<br>
<!--strong>
@lang('messages.total_area')
:</strong> {{ $location->area }} <br -->
@if ($location->notes)
<br>
<strong>
@lang('messages.notes')
:</strong> {{ $location->notes }}
<br>
@endif
<br>
@can ('update', $location)
			    <div class="col-sm-6">
				<a href="{{ url('locations/'. $location->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.location_ancestors_and_children')
                </div>

                <div class="panel-body">
	@if ($location->getAncestors()->count())
	@foreach ($location->getAncestors() as $ancestor)
		<a href=" {{ url('locations/'. $ancestor->id ) }} ">{{ $ancestor->name }} </a> &gt;
	@endforeach
	@endif
	 {{ $location->name }}
	 @if ($location->getDescendants()->count())

	<ul>
	 @foreach ($location->children as $child)
		<li> <a href=" {{url('locations/' . $child->id) }}"> {{ $child->name }} </a>
			{{ $child->getDescendants()->count() ? '(+' . $child->getDescendants()->count() . ')' : ''}}
		</li>
@endforeach
</ul>
@endif

    

                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang ('messages.location_map')
		    @if ($location->simplified)
		    -
		    @lang ('messages.simplified_map')
		    @endif
                </div>

                <div class="panel-body" id="map" style="
        height: 400px;
        width: 100%;
">
	@if (empty ($location->geomArray))
	@lang ('messages.location_map_error')
	@endif

                </div>
            </div>
        </div>
    </div>

<?php 
function getZoomLevel($area) {
	if ($area > 400)
		return 3;
	if ($area > 200)
		return 4;
	if ($area > 50)
		return 5;
	if ($area > 15)
		return 6;
	if ($area > 5)
		return 7;
	if ($area > 0.8)
		return 8;
	if ($area > 0.1)
		return 9;
	if ($area > 0.01)
		return 10;
	if ($area > 0.001)
		return 11;
	// default zoom level
	return 12;
}
?>


<script>
      function initMap() {
        var uluru = {lat: {{$location->centroid['y']}}, lng: {{$location->centroid['x']}}  };
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: {{ getZoomLevel($location->area) }},
          center: uluru
	});
@if (count($location->geomArray) > 1) 
	var polygon = new google.maps.Polygon({
	paths: [
<?php $i = 0; ?>
	@foreach ($location->geomArray as $point)
	 {lat: {{$point['y']}}, lng: {{$point['x']}}},
	 @endforeach
	 ],
	 map: map,
	 strokeColor:'#00FF00',
          strokeOpacity: 0.7,
          strokeWeight: 2,
          fillColor: '#00FF00',
          fillOpacity: 0.3
        });
@else
<?php $point = $location->geomArray[0]; ?>
  var marker = new google.maps.Marker({
  position: {lat: {{$point['y']}}, lng: {{$point['x']}} },
    map: map,
    title: 'Plot'
  });
@endif
      }
</script>

<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.gmaps_api_key') }}&callback=initMap">
    </script>


@endsection
