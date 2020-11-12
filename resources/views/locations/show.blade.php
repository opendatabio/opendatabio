@extends('layouts.app')
@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-9">
    <div class="panel panel-default">

      <!-- Project basic info block -->
      <div class="panel-heading">
        @lang('messages.location')
        <span class="history" style="float:right">
          <a href="{{url("locations/$location->id/activity")}}">
          @lang ('messages.see_history')
          </a>
        </span>
      </div>
      <div class="panel-body">
        <div class="col-sm-12">
          <p>
            <strong>
            @lang('messages.location_name')
            :
            </strong>
            {{ $location->name }} {!! $location->coordinatesSimple !!}
          </p>
          <p>
		    <strong>
          @lang('messages.adm_level')
          :
        </strong>
        @lang ('levels.adm_level.' . $location->adm_level)
        </p>

        @if ($location->altitude)
          <p>
          <strong>
            @lang('messages.altitude') :
          </strong> {{ $location->altitude }}
          </p>
        @endif


        <!-- Plot specific info when dimensions is present -->
        <!-- X, Y, etc) -->
        @if ($location->x or $location->y)
          <p>
          <strong>
            @lang('messages.dimensions')
            :</strong> X: {{ $location->x }}m, Y: {{$location->y}}m</a>
          </p>
        @endif
        @if ($location->startx or $location->starty)
           <p>
            <strong>
              @lang('messages.position')
              :</strong> X: {{ $location->startx }}m, Y: {{$location->starty}}m</a>
              </p>
        @endif


        @if ($location->datum)
          <p>
          <strong>
            @lang('messages.datum')
            :</strong> {{ $location->datum }}
            </p>
        @endif

        @if ($location->uc_id)
          <p>
          <strong>
            @lang('messages.uc')
            :</strong> {!! $location->uc->rawLink() !!}
            </p>
        @endif
<p>
        <strong>
          @lang('messages.total_descendants')
          :</strong>
          <a data-toggle="collapse" href="#related_taxa" >
            {{ $location->getDescendants()->count() }}
          </a>
  </p>

        @if ($location->notes)
          <p>
          <strong>
            @lang('messages.notes')
            :</strong> {{ $location->notes }}
          </p>

          @endif
        </div>

        <!-- BUTTONS -->
        <div class="col-sm-12">
          <br>          
          @if ($location->measurements()->count())
              <a href="{{ url('locations/'. $location->id. '/measurements')  }}" class="btn btn-default">
                <i class="fa fa-btn fa-search"></i>
                {{ $location->measurements()->count() }}
                @lang('messages.measurements')
              </a>
              &nbsp;&nbsp;
          @else
            @can ('create', App\Measurement::class)
                <a href="{{ url('locations/'. $location->id. '/measurements/create')  }}" class="btn btn-default">
                  <i class="fa fa-btn fa-plus"></i>
                  @lang('messages.create_measurements')
                </a>
                &nbsp;&nbsp;
              @endcan
          @endif

         @if ($location->vouchers()->count())
              <a href="{{ url('locations/'. $location->id. '/vouchers')  }}" class="btn btn-default">
               <i class="fa fa-btn fa-search"></i>
               {{ $location->vouchers()->count() }}
               @lang('messages.vouchers')
             </a>
             &nbsp;&nbsp;
         @else
           @can ('create', App\Voucher::class)
               <a href="{{url ('locations/' . $location->id . '/vouchers/create')}}" class="btn btn-default">
                 <i class="fa fa-btn fa-plus"></i>
                 @lang('messages.create_voucher')
               </a>
             &nbsp;&nbsp;
           @endcan
         @endif

         @if ($location->plants()->count())
             <a href="{{ url('locations/'. $location->id. '/plants')  }}" class="btn btn-default">
               <i class="fa fa-btn fa-search"></i>
               {{ $location->plants()->count() }}
               @lang('messages.plants')
             </a>
             &nbsp;&nbsp;
         @else
           @can ('create', App\Plant::class)
                <a href="{{url ('locations/' . $location->id . '/plants/create')}}" class="btn btn-default">
                 <i class="fa fa-btn fa-plus"></i>
                 @lang('messages.create_plant')
               </a>
               &nbsp;&nbsp;
           @endcan
         @endif
       </div>
       <div class="col-sm-12">
         <br><br>
         @can ('update', $location)
				        <a href="{{ url('locations/'. $location->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
                  @lang('messages.edit')
				        </a>
                &nbsp;&nbsp;
        @endcan
        @can ('create', App\Picture::class)
            <a href="{{ url('locations/'. $location->id. '/pictures/create')  }}" class="btn btn-success">
              <i class="fa fa-btn fa-plus"></i>
              @lang('messages.create_picture')
            </a>
            &nbsp;&nbsp;
        @endcan
      </div>
    </div>
  </div>


<!-- RELATED TAXA  - DESCENDANTS AND ANCESTORS -->
<div class="panel panel-default">
  <div class="panel-heading">
    <a data-toggle="collapse" href="#related_taxa" class="btn btn-default">
      @lang('messages.location_ancestors_and_children')
    </a>
  </div>
  <div class="panel-collapse collapse"  id='related_taxa'>
    <div class="panel-body">
      <strong>@lang('messages.location_ancestors')</strong>:
      <br>
	     @if ($location->getAncestors()->count())
	       @foreach ($location->getAncestors() as $ancestor)
           @if ($ancestor->adm_level != -1)
          {!! $ancestor->rawLink() !!} &gt;
          @endif
	       @endforeach
	     @endif
	      {{ $location->name }}
        <br>
        @if($location->getDescendants()->count())
        <hr>
        <strong>@lang('messages.location_children')</strong>:
        <br><br>
    {!! $dataTable->table() !!}
    @endif
  </div>
</div>
</div>


<!--- PLANTS ON LOCATION  -->
@if ($location->plants()->count() and isset($chartjs))
<div class="panel panel-default">
  <div class="panel-heading">
    <a data-toggle="collapse" href="#plants_map" class="btn btn-default">
    @lang('messages.plants')
  </a>
  </div>
  <div class="panel-body panel-collapse collapse"  id='plants_map'>
    @if (isset($chartjs))
      <div style = "width:100%; height:400px; overflow:auto;">
        {!! $chartjs->render() !!}
      </div>
    @endif
  </div>
</div>
@endif

<!--- PICTURES BLOCK -->
@if ($location->pictures->count())
{!! View::make('pictures.index', ['pictures' => $location->pictures]) !!}
@endif


<!-- MAP LOCATION -->
<div class="panel panel-default">
  <div class="panel-heading">
    <a data-toggle="collapse" href="#map_box" class="btn btn-default">
    @lang ('messages.location_map')
		   @if ($location->simplified)
		    -
		    @lang ('messages.simplified_map')
		    @endif
    </a>
  </div>
  <div class="panel-collapse collapse" id='map_box'>
  <div class="panel-body" id="map" style="
        height: 400px;
        width: 100%;">
	  @if (empty ($location->geomArray))
	     @lang ('messages.location_map_error')
	   @endif
  </div>
  </div>
</div>



  </div>
</div>
<!-- END -->

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

@endsection


@push ('scripts')
{!! $dataTable->scripts() !!}

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
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.gmaps_api_key') }}&callback=initMap"></script>

@endpush
