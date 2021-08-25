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
            {{ $location->name }}
          </p>
          <p>
		    <strong>
          @lang('messages.adm_level')
          :
        </strong>
          @lang ('levels.adm_level.' . $location->adm_level)
          [ @lang('messages.geometry'): {{ $location->geomType }}]
        </p>

        <p>
        <strong>
          @lang('messages.centroid') :
        </strong> {{ $location->centroid_raw }}
        </p>





        <!-- Plot specific info when dimensions is present -->
        <!-- X, Y, etc) -->
        @if ($location->x or $location->y)
          <p>
          <strong>
            @lang('messages.dimensions')
            :</strong>
            @if ($location->adm_level == 101)
             @lang('messages.transect_length'): {{ $location->transect_length }}&nbsp;m, @lang('messages.transect_buffer'): {{$location->y}}&nbsp;m
             @else
             x: {{ $location->x }} m  | y: {{$location->y}} m  | area: {{$location->area}} m<sup>2</sup>
            @endif
          </p>
        @endif


        @if ($location->startx or $location->starty)
           <p>
            <strong>
              @lang('messages.position')
              :</strong> X: {{ $location->startx }}m, Y: {{$location->starty}}m</a>
              </p>
        @endif

        <!--- should remove and standardize to wgs84
        @if ($location->datum)
          <p>
          <strong>
            @lang('messages.datum')
            :</strong> {{ $location->datum }}
            </p>
        @endif
        --->

          <p>
          <strong>
            @lang('messages.location_belongs')
            :</strong>
            <br>&nbsp;&nbsp;&nbsp;-&nbsp;{!! $location->parent->rawLink() !!}
            @if ($location->relatedLocations->count())
            @foreach($location->relatedLocations as $related)
              <br>&nbsp;&nbsp;&nbsp;-&nbsp;{!! $related->relatedLocation->rawLink() !!}
            @endforeach
            @endif
          </p>


  <p>
        <strong>
          @lang('messages.total_descendants')
          :</strong>
          <a data-toggle="collapse" href="#related_taxa" >
            {{ $location->childrenCount() }}
          </a>
  </p>

  @if ($location->altitude)
    <p>
    <strong>
      @lang('messages.altitude') :
    </strong> {{ $location->altitude }}
    </p>
  @endif


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
          @if ($location->getCount('all',null,'measurements'))
              <a href="{{ url('locations/'. $location->id. '/measurements')  }}" class="btn btn-default">
                <i class="fa fa-btn fa-search"></i>
                {{ $location->getCount('all',null,'measurements') }}
                @lang('messages.measurements')
              </a>
              &nbsp;&nbsp;
          @else
            @can ('create', App\Models\Measurement::class)
                <a href="{{ url('locations/'. $location->id. '/measurements/create')  }}" class="btn btn-default">
                  <i class="fa fa-btn fa-plus"></i>
                  @lang('messages.create_measurements')
                </a>
                &nbsp;&nbsp;
              @endcan
          @endif

         @if ($location->getCount('all',null,'vouchers'))
              <a href="{{ url('locations/'. $location->id. '/vouchers')  }}" class="btn btn-default">
               <i class="fa fa-btn fa-search"></i>
               {{ $location->getCount('all',null,'vouchers')}}
               @lang('messages.vouchers')
             </a>
             &nbsp;&nbsp;
         @endif

         @if ($location->getCount('all',null,'individuals'))
             <a href="{{ url('individuals/'. $location->id. '/location')  }}" class="btn btn-default">
               <i class="fa fa-btn fa-search"></i>
               {{ $location->getCount('all',null,'individuals')}}
               @lang('messages.individuals')
             </a>
             &nbsp;&nbsp;
         @else
           @can ('create', App\Models\Individual::class)
                <a href="{{url ('individuals/' . $location->id . '/location/create')}}" class="btn btn-default">
                 <i class="fa fa-btn fa-plus"></i>
                 @lang('messages.create_individual')
               </a>
               &nbsp;&nbsp;
           @endcan
         @endif
         @if ($location->getCount('all',null,'taxons'))
             <a href="{{ url('taxons/'. $location->id. '/location')  }}" class="btn btn-default">
               <i class="fa fa-btn fa-search"></i>
               {{ $location->getCount('all',null,'taxons')}}
               @lang('messages.taxons')
             </a>
             &nbsp;&nbsp;
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
        <!-- this will show only if no media as media are shown below -->
        @can ('create', App\Models\Media::class)
          <a href="{{ url('locations/'. $location->id. '/media-create')  }}" class="btn btn-success">
              <i class="fa fa-btn fa-plus"></i>
              <i class="fas fa-photo-video"></i>
              <i class="fas fa-headphones-alt"></i>
              @lang('messages.create_media')
          </a>
          &nbsp;&nbsp;
        @endcan
        <input type="hidden" name="map-route-url" value="{{ route('maprender') }}">
        &nbsp;
        <button type="submit" class="btn btn-primary" id="map_location">
        <i class="fas fa-map-marked-alt fa-1x"></i>&nbsp;@lang('messages.map')
        </button>
        <div class="spinner" id="mapspinner" > </div>
        <div id="ajax-error" class="collapse alert alert-danger">
        @lang('messages.whoops')
        </div>



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
        @if($location->childrenCount()>0)
        <hr>
        <strong>@lang('messages.location_children')</strong>:
        <br><br>
    {!! $dataTable->table() !!}
    @endif
  </div>
</div>
</div>


<!--- individualS ON LOCATION  -->
@if ($location->individuals()->count()  and isset($chartjs))
<div class="panel panel-default">
  <div class="panel-heading">
    <a data-toggle="collapse" href="#individuals_map" class="btn btn-default">
    @lang('messages.individuals')
  </a>
  </div>
  <div class="panel-body panel-collapse collapse"  id='individuals_map'>
    @if (isset($chartjs))
      <div style = "width:100%; height:400px; overflow:auto;">
        {!! $chartjs->render() !!}
      </div>
    @endif
  </div>
</div>
@endif



<!--- MEDIA BLOCK -->
@if (null != $media)
  {!! View::make('media.index-model', ['model' => $location, 'media' => $media ]) !!}
@endif



<!-- MAP LOCATION -->
<div class="panel panel-default" id='map-box' tabindex='1' hidden>
  <div class="panel-heading">
    @lang ('messages.location_map')
		   @if ($location->is_drawn)
		    -
		    @lang ('messages.geometry_drawn')
		   @endif
  </div>
    <div class="panel-body">
      <input type="hidden" name="location_id" value="{{ $location->id }}">
      <input type="hidden" name="location_json" value="" id='location_json'>
      <div id="osm_map" style="
          height: 400px;
          width: 100%;">
     </div>
     <div id="popup" class="ol-popup">
      <a href="#" id="popup-closer" class="ol-popup-closer"></a>
      <div id="popup-content" ></div>
      </div>
    </div>
</div>



  </div>
</div>
<!-- END -->

@endsection


@push ('scripts')
{!! $dataTable->scripts() !!}


<script>
    const table = $('#dataTableBuilder');
    table.on('preXhr.dt',function(e,settings,data) {
      data.adm_level = $("#location_level option").filter(':selected').val();
      /* an any defined in the export form */
      data.location = "{{ $location->id }}";
      /*console.log(data.level,data.project,data.location,data.taxon); */
    });
    $('#location_level').on('change',function() {
       table.DataTable().ajax.reload();
       return false;
    });
</script>



<script >
/** Ajax handling for mapping */
$("#map_location").click(function(e) {
  var isrendered = $("#location_json").val();
  if (isrendered == '') {
  $( "#mapspinner" ).css('display', 'inline-block');
  $.ajaxSetup({ // sends the cross-forgery token!
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  })
  $.ajax({
    type: "POST",
    url: $('input[name="map-route-url"]').val(),
    dataType: 'json',
          data: {
              'location_id': $('input[name="location_id"]').val(),
          },
    success: function (data) {
      $( "#mapspinner" ).hide();
      if ("error" in data) {
        $( "#ajax-error" ).collapse("show");
        $( "#ajax-error" ).text(data.error);
      } else {
        // ONLY removes the error if request is success
        $( "#ajax-error" ).collapse("hide");
        $("#location_json").val(data.features);
        $("#map-box").show();
        $("#map-box").focus();
        window.my_map.display();

      }
    },
    error: function(e){
      $( "#spinner" ).hide();
      $( "#ajax-error" ).collapse("show");
      $( "#ajax-error" ).text('Error sending AJAX request');
    }
  })
  } else {
    if ($('#map-box').is(":visible")) {
      $('#map-box').hide();
    } else {
      $('#map-box').show();
      $('#map-box').focus();
    }
  }
});

</script>


@endpush
