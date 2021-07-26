@extends('layouts.app')
@section('content')
<div class="container">
<?php

$location_test = '
{
  "type": "FeatureCollection",
  "features": [
      {
        "type": "Feature",
        "geometry":
        {
              "type": "Polygon",  "coordinates": [
                [[-90.0, -45.0], [-90.0, 45.0], [90.0, 45.0], [90.0, -45.0], [-90.0, -45.0]]
                ]
          },
          "properties": {
                "name": "larger", "adm_level": 2
          }
      },
      {
        "type": "Feature",
        "geometry":
        {
          "type": "Polygon",
          "coordinates": [
            [[-45.0, -22.0], [-45.0, 22.0], [45.0, 22.0], [45.0, -22.0], [-45.0, -22.0]]
            ]
        },
        "properties":
        {
            "name": "smaller",
            "adm_level": 3
        }
      }
    ]
}
';
$location_test = $location->generateFeatureCollection();
//$location_test = $location->geomjson;
$location_centroid = $location->long.",".$location->lat;

?>

  <input type="hidden" name="location_centroid" value="{{ $location_centroid }}" id='$location_centroid'>
  <input type="hidden" name="location_json" value="{{ $location_test }}" id='location_json'>
  <div id="osm_map"></div>

</div>
<!-- END -->


@endsection


@push ('scripts')
<style>
 #osm_map {
   height: 400px;
   width: 100%;
 }
</style>

<script type="text/javascript">
    window.my_map.display();
</script>




<!--- <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.gmaps_api_key') }}&callback=initMap"></script> --->

@endpush
