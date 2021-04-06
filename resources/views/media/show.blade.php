@extends('layouts.app')
@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">
      <div class="panel-heading">
        @lang('messages.media_for')
        @php
        $basename = Lang::get("classes.".get_class($media->model));
        $fullname = $media->model->rawLink();
        $fileUrl = $media->getUrl();
        @endphp
      <strong>{!! $basename." ".$fullname !!}</strong>

      <span class="history" style="float:right">
      <a href="{{url("media/$media->id/activity")}}">
      @lang ('messages.see_history')
      </a>
      </span>


    </div>
		<div class="panel-body">
      @if ($media->title)
        <h4>{{ $media->title }}</h4>
        @if ($media->all_collectors)
          By {{ $media->all_collectors }} {{$media->year }}
        @endif
      @endif

      @if ($media->media_type == 'image')
          <img src="{{ $fileUrl }}" alt="" class='vertical_center'>
      @endif


      @if ($media->media_type == 'video')
        <video controls class='vertical_center'>
          <source src="{{ $fileUrl }}"  type="{{ $media->mime_type }}"/>
          Your browser does not support the video tag.
        </video>
      @endif

      @if ($media->media_type == 'audio')
          <center >
          <br><br>
          <i class="fas fa-file-audio fa-6x"></i>
          <br><br>
          <audio controls >
            <source src="{{ $fileUrl }}"  type="{{ $media->mime_type }}"/>
            </audio>
          </center>
      @endif

      <br>
      <p>
        @php
          $mediatype = ucfirst($media->media_type);
        @endphp
        <strong>
          @lang('messages.media_linked_to',compact('mediatype'))
        </strong>:
        <h4>
        {{ Lang::get("classes.".get_class($media->model)) }}&nbsp;
        {!! $media->model->rawLink() !!}
        </h4>
      </p>

      <!-- DEFINE LICENSE IMAGE BASED ON LICENSE -->
      @if ($media->license)
        @php
          $license = explode(" ",$media->license);
          $license_logo = 'images/'.mb_strtolower($license[0]).".png";
        @endphp
        <br>
        <p>
          <strong>@lang('messages.license')</strong>:
          <br>
          {{ $media->license }}
          <br>
          <a href="http://creativecommons.org/license" target="_blank">
            <img src="{{ asset($license_logo) }}" alt="{{ $media->license }}" width='100px'>
          </a>
        </p>
      @endif

      @if ($media->tags->count())
      <br>
      <p>
        <strong>
          @lang('messages.tagged_with')
        </strong>:
        {!! $media->tagLinks !!}
      </p>
      @endif



@if (isset($media->citation))
  <hr>
  <p>
    <strong>@lang('messages.howtocite')</strong>:
    <br>
    {!! $media->citation !!}&nbsp;&nbsp;&nbsp;<a data-toggle="collapse" href="#bibtex" class="btn-sm btn-primary">BibTeX</a>
  </p>
  <hr>
  <div id='bibtex' class='panel-collapse collapse'>
    <pre><code>{{ $media->bibtex }}</code></pre>
  </div>
@endif

@if (isset($media->notes))
  <br>
  <p>
    <strong>@lang('messages.notes')</strong>:
    <br>
    {{ $media->notes }}
  </p>
@endif

<p>
@can ('update', $media)
	<a class="btn btn-success" href="{{url ('media/' . $media->id . '/edit')}}">
	    <i class="fa fa-btn fa-plus"></i>
      @lang('messages.edit')
	</a>
@endcan

@if($media->measurements->count() == 0)
  @can ('delete', $media)
    &nbsp;&nbsp;
  	<form action="{{ url('media/'.$media->id) }}" method="POST" class="form-horizontal">
      {{ csrf_field() }}
      {{ method_field('DELETE') }}
       <button type="submit" class="btn btn-danger">
         <i class="fa fa-btn fa-plus"></i>
         @lang('messages.remove_media')
       </button>
    </form>
@endcan
@endif

</p>

            </div>
</div>
    </div>

<style >

.vertical_center {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 99%;
  width: 99%;
}

</style>


@endsection
