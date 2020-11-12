@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">
        <div class="panel-heading">
            @lang('messages.tags')
        </div>
		    <div class="panel-body">

        <p><strong>
          @lang('messages.name')
          : </strong> {{ $tag->name }}
        </p>
		    <p><strong>
          @lang('messages.description')
          : </strong> {{ $tag->description }} </p>

          <!-- RELATED MODELS BUTTONS -->
          <p>
            @if ($tag->datasets()->count())
              <a href="{{ url('datasets/'. $tag->id. '/tags')  }}" class="btn btn-default">
                <i class="fa fa-btn fa-search"></i>
                {{ $tag->datasets()->count() }}
                @lang('messages.datasets')
              </a>
              &nbsp;&nbsp;
            @endif
            @if ($tag->projects()->count())
              <a href="{{ url('projects/'. $tag->id. '/tags')  }}" class="btn btn-default">
                <i class="fa fa-btn fa-search"></i>
                {{ $tag->projects()->count() }}
                @lang('messages.projects')
              </a>
              &nbsp;&nbsp;
            @endif
            @if ($tag->pictures->count())
              <a href="#pictures_block" class="btn btn-default">
                {{ $tag->pictures->count() }}
                @lang('messages.pictures')
              </a>
              &nbsp;&nbsp;
            @endif
          </p>

@can ('update', $tag)
<p>
  <a href="{{ url('tags/'.$tag->id.'/edit') }}" class="btn btn-success">
	  <i class="fa fa-btn fa-plus"></i>
    @lang('messages.edit')
	</a>
</p>
@endcan

    </div>
</div>

@if ($tag->pictures->count())
  {!! View::make('pictures.index', ['pictures' => $tag->pictures]) !!}
@endif

    </div>
</div>
@endsection
