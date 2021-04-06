<div class="panel panel-default">
  <div class="panel-heading">
    @if (isset($model))
      @lang('messages.media_for'):
      &nbsp;
      <strong>{{ class_basename($model) }}</strong>
      {!! $model->rawLink() !!}
      @php
        $urlCreateMedia = $model->getTableName()."/".$model->id."/media-create";
        $urlShowAllMedia = "media/".$model->id."/".$model->getTableName();
      @endphp
      @can ('create', App\Models\Picture::class)
        &nbsp;&nbsp;
        <a href="{{ url($urlCreateMedia)  }}" class="btn btn-success btn-sm">
            <i class="fa fa-btn fa-plus"></i>
            <i class="fas fa-photo-video"></i>
            <i class="fas fa-headphones-alt"></i>
        </a>
      @endcan
      @if (isset($media))
        &nbsp;&nbsp;
        <a href="{{ url($urlShowAllMedia)}}" class='btn btn-primary btn-sm'>
          <i class="far fa-eye"></i>
          @lang('messages.media_see_all') {{ $media->total() }}
        </a>
      @endif
    @else
      @lang('messages.media_gallery'):
    @endif
  </div>
  <div class="panel-body"  id='image_block'>
  <div class="image_container">
    <div class="image_grid">
    		@if(isset($media))
           @php
             $showLinks = false;
             if ($media->hasPages()) {
               $showLinks = true;
             }
           @endphp
        	@foreach($media as $singleMedia)
            @php
              $fileUrl = $singleMedia->getUrl();
              if (file_exists($singleMedia->getPath('thumb'))) {
                $thumbUrl = $singleMedia->getUrl('thumb');
              } else {
                $thumbUrl = $fileUrl;
              }
            @endphp
            <div class="image_cell">
              <a href="{{ url("media/".$singleMedia->id) }}" alt='See record'>
                <!-- <i class="fas fa-eye fa-1x"></i> -->
                <i class="fas fa-search-plus"></i>
              </a>
              @can ('update', $singleMedia)
                &nbsp;&nbsp;
              	<a href="{{url ('media/' . $singleMedia->id . '/edit')}}" >
                    <i class="fas fa-edit"></i>
              	</a>
              @endcan
              <span style='float: right;'>
                {!! $singleMedia->license_icons() !!}
              </span>
              <figure class='thumbnail'>

              @if ($singleMedia->media_type == 'image')
                <a href="{{ url("media/".$singleMedia->id) }}" style="cursor: pointer;">
                  <img src="{{ $thumbUrl }}" alt="" class='audio_control'>
                </a>
              @endif
              @if ($singleMedia->media_type == 'video')
                @if (file_exists($singleMedia->getPath('thumb'))) {
                  <video controls poster="{{ $singleMedia->getUrl('thumb') }}" class='audio_control vertical_center'>
                @else
                  <video controls class='vertical_center'>
                @endif
                  <source src="{{ $fileUrl }}"  type="{{ $singleMedia->mime_type }}"/>
                    Your browser does not support the video tag.
                </video>
              @endif
              @if ($singleMedia->media_type == 'audio')
                  <center>
                  <br><br>
                  <i class="fas fa-file-audio fa-6x"></i>
                  <br><br>
                  <audio controls  class='vertical_center'>
                    <source src="{{ $fileUrl }}"  type="{{ $singleMedia->mime_type }}"/>
                    </audio>
                  </center>
              @endif
              </figure>

              {!! $singleMedia->gallery_citation("fa-1.5x") !!}

  					</div>
  				@endforeach
    </div>
    @if ($showLinks)
    <hr>
    <div >
      {{ $media->links() }} &nbsp; &nbsp;
    </div>
    @endif
    @endif

    </div>
    </div>
</div>
<style >


.image_container {
  margin: 0 auto;
  max-width: 1200px;
  padding: 0 1rem;
}

.thumbnail {
  height: 200px;
  width: 200px;
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 2%;
  overflow: hidden;
  display: block;
}

.image_cell  {
  width: 200px;
  display: block;
  margin: 1rem;
}

.vertical_center {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 99%;
  width: 99%;
}


@media screen and (min-width: 600px) {
  .image_grid {
    display: flex;
    flex-wrap: wrap;
    flex-direction: row;
  }
  /*
  .image_cell {
    width: 50%;
    width: calc(50% - 2rem);
  }
  */
}

/*
@media screen and (min-width: 1000px) {
  .image_cell {
    width: calc(33.3333% - 2rem);
  }
}
*/



</style>
