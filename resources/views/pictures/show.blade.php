@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.picture')
                </div>

		<div class="panel-body">
<a href="{{$picture->url()}}">
    <img src = "{{$picture->url() }}" class="picture">
</a>
<p>
	<strong>
	@lang('messages.description')
:</strong>
    {{ $picture->description }}
</p>
<p>
	<strong>
	@lang('messages.picture_of')
:</strong>
    {!! $picture->object->rawLink() !!}
</p>

@if ($picture->tags->count())
<p> <strong> @lang('messages.tags') :</strong>
    <ul>
    @foreach ($picture->tags as $tag) 
    <li><a href="{{url('tags/'. $tag->id)}}">{{$tag->name}}</a></li>
    @endforeach
    </ul>
</p>
@endif
@if ($picture->collectors->count())
<p> <strong> @lang('messages.credits') :</strong>
    <ul>
    @foreach ($picture->collectors as $collector) 
    <li>{!! $collector->rawLink() !!}</li>
    @endforeach
    </ul>
</p>
@endif
<p>
@can ('update', $picture)
				<a class="btn btn-success" href="{{url ('pictures/' . $picture->id . '/edit')}}">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')
				</a>
@endcan
            </div>
</div>
    </div>
@endsection
