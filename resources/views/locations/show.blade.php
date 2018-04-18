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
:</strong> {{ $location->name }} {{ $location->coordinatesSimple }} 
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
:</strong> {!! $location->uc->rawLink() !!}
<br>
@endif
<strong> 
@lang('messages.total_descendants')
:</strong> {{ $location->getDescendants()->count() }}
<br>
@if ($location->notes)
<br>
<strong>
@lang('messages.notes')
:</strong> {{ $location->notes }}
<br>
@endif
<br>
@if ($location->measurements()->count())
<div class="col-sm-3">
    <a href="{{ url('locations/'. $location->id. '/measurements')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $location->measurements()->count() }}
@lang('messages.measurements')
    </a>
</div>
@else 
    @can ('create', App\Measurement::class)
<div class="col-sm-3">
    <a href="{{ url('locations/'. $location->id. '/measurements/create')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
@lang('messages.create_measurements')
    </a>
</div>
    @endcan
@endif

@if ($location->vouchers()->count())
<div class="col-sm-3">
    <a href="{{ url('locations/'. $location->id. '/vouchers')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $location->vouchers()->count() }}
@lang('messages.vouchers')
    </a>
</div>
@else
@can ('create', App\Voucher::class)
<div class="col-sm-4">
<a href="{{url ('locations/' . $location->id . '/vouchers/create')}}" class="btn btn-default">
    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_voucher')
</a>
</div>
@endcan
@endif

@if ($location->plants()->count())
<div class="col-sm-3">
    <a href="{{ url('locations/'. $location->id. '/plants')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $location->plants()->count() }}
@lang('messages.plants')
    </a>
</div>
@else
@can ('create', App\Plant::class)
<div class="col-sm-4">
<a href="{{url ('locations/' . $location->id . '/plants/create')}}" class="btn btn-default">
    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_plant')
</a>
</div>
@endcan
@endif

@can ('update', $location)
			    <div class="col-sm-3">
				<a href="{{ url('locations/'. $location->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
    @can ('create', App\Picture::class)
<div class="col-sm-6">
    <a href="{{ url('locations/'. $location->id. '/pictures/create')  }}" class="btn btn-success">
        <i class="fa fa-btn fa-search"></i>
@lang('messages.create_picture')
    </a>
</div>
 @endcan
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
@if ($location->pictures->count())
{!! View::make('pictures.index', ['pictures' => $location->pictures]) !!}
@endif
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.location_ancestors_and_children')
                </div>

                <div class="panel-body">
	@if ($location->getAncestors()->count())
	@foreach ($location->getAncestors() as $ancestor)
        @if ($ancestor->adm_level != -1)
        {!! $ancestor->rawLink() !!} &gt;
        @endif
	@endforeach
	@endif
	 {{ $location->name }}
	 @if ($location->getDescendants()->count())

	<ul>
	 @foreach ($location->children as $child)
		<li> {!! $child->rawLink() !!} </a>
			{{ $child->getDescendants()->count() ? '(+' . $child->getDescendants()->count() . ')' : ''}}
		</li>
@endforeach
</ul>
@endif
                </div>
            </div>
	@if ($location->plants()->count() and isset($chartjs))
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.plants')
                </div>
                <div class="panel-body">
@if (isset($chartjs))
<div style = "width:100%; height:400px; overflow:auto;">
{!! $chartjs->render() !!}
</div>
@endif
                </div>
            </div>
	@endif
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
        // display the main object
@if (in_array($location->geomType, ['polygon', 'multipolygon'])) 
@foreach ($location->geomArray as $polygon)
	new google.maps.Polygon({
	paths: [
	@foreach ($polygon as $point)
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
@endforeach
@elseif ($location->geomType == "point")
<?php $point = $location->geomArray; ?>
  new google.maps.Marker({
  position: {lat: {{$point['y']}}, lng: {{$point['x']}} },
    map: map,
    title: 'Plot'
  });
@endif
    // now we display children
@foreach ($plot_children as $child)
@if (!is_null($child) and $child->geomType == "point")
  new google.maps.Marker({
  position: {lat: {{$child->geomArray['y']}}, lng: {{$child->geomArray['x']}} },
    map: map,
    title: '{{$child->name}}'
  });
@endif
@endforeach
      }
</script>

<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.gmaps_api_key') }}&callback=initMap">
</script>
@endsection
