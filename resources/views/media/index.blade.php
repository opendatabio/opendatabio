@extends('layouts.app')
@section('content')
<div class="container-fluid">
  <div class="col-sm-offset-2 col-sm-8">
    @if (isset($model) and isset($media))
    @php
      $objects  = [
          'model' => $model,
          'media' => $media
      ];
      @endphp
      {!! View::make('media.index-model',$objects) !!}
    @else
      @lang('messages.no_media_found')
    @endif

  </div>
</div>
@endsection
