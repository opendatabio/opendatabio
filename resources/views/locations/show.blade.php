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
@lang('messages.total_descendants')
:</strong> {{ $location->descendants->count() }}
<br>
<strong>
@lang('messages.total_area')
:</strong> {{ $location->geom }}
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.location_ancestors_and_children')
                </div>

                <div class="panel-body">
	@if ($location->ancestors->count())
	@foreach ($location->ancestors as $ancestor)
		<a href=" {{ url('locations/'. $ancestor->id ) }} ">{{ $ancestor->name }} </a> &gt;
	@endforeach
	@endif
	 {{ $location->name }}
	 @if ($location->descendants->count())

	<ul>
	 @foreach ($location->children as $child)
		<li> <a href=" {{url('locations/' . $child->id) }}"> {{ $child->name }} </a>
			{{ $child->descendants->count() ? '(+' . $child->descendants->count() . ')' : ''}}
		</li>
@endforeach
</ul>
@endif

    

                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.location_map')
                </div>

                <div class="panel-body" id="map" style="
        height: 400px;
        width: 100%;
">

                </div>
            </div>
        </div>
    </div>

<script>
      function initMap() {
        var uluru = {lat: {{$location->geomArray[0]['y']}}, lng: {{$location->geomArray[0]['x']}}  };
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 4,
          center: uluru
	});
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
      }
</script>

<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ getenv('GMAPS_API_KEY') }}&callback=initMap">
    </script>


@endsection
