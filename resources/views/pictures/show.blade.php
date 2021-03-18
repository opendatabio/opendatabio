@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.picture_of')
                    @php
                      $basename = class_basename($picture->object()->withoutGlobalScopes()->first());
                      $fullname = $picture->object->rawLink();
                    @endphp
                    <strong>{!! $basename." ".$fullname !!}</strong>
                </div>

		<div class="panel-body">
<a href="{{$picture->url()}}">
    <img src = "{{$picture->url() }}" class="picture">
</a>
<br>

<p>
  <h4><strong>{!! $picture->title !!}</strong></h4>
  ({{ $picture->year }}) <br>
  By {{ $picture->all_collectors }} <br>
  <br>
  @lang('messages.picture_of') {!! $basename." ".$fullname !!}
</p>


@if ($picture->tags)
<br>
<p>
  <strong>
    @lang('messages.tagged_with')
  </strong>:
  {!! $picture->tagLinks !!}
</p>
@endif

<!-- DEFINE LICENSE IMAGE BASED ON LICENSE -->
@if ($picture->license)
  @php
    $license = explode(" ",$picture->license);
    $license_logo = 'images/'.mb_strtolower($license[0]).".png";
  @endphp
  <br>
  <p>
    <strong>@lang('messages.license')</strong>:
    <br>
    {{ $picture->license }}
    <br>
    <a href="http://creativecommons.org/license" target="_blank">
      <img src="{{ asset($license_logo) }}" alt="{{ $picture->license }}" width='100px'>
    </a>
  </p>
@endif

@if (isset($picture->citation))
  <br>
  <p>
    <strong>@lang('messages.howtocite')</strong>:
    <br>
    {!! $picture->citation !!}
  </p>
@endif

@if (isset($picture->notes))
  <br>
  <p>
    <strong>@lang('messages.notes')</strong>:
    <br>
    {{ $picture->notes }}
  </p>
@endif



@can ('update', $picture)
  <p>
				<a class="btn btn-success" href="{{url ('pictures/' . $picture->id . '/edit')}}">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')
				</a>
  </p>
@endcan
            </div>
</div>
    </div>
@endsection
