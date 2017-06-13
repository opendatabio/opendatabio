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
:</strong> {{ $location->name }}
<br>
<strong> 
@lang('messages.total_descendants')
:</strong> {{ $location->descendants->count() }}
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.location_ancestors_and_children')
                </div>

                <div class="panel-body">
	@if ($location->ancestors->count())
	@foreach ($location->ancestors as $ancestor)
		<a href=" {{ url('locations/'. $ancestor->id ) }} ">{{ $ancestor->name }} </a> &gt;
	@endforeach
	@endif
	 {{ $location->name }}
	 @if ($location->descendants->count())

	<ul>
	 @foreach ($location->children as $child)
		<li> <a href=" {{url('locations/' . $child->id) }}"> {{ $child->name }} </a>
			{{ $child->descendants->count() ? '(+' . $child->descendants->count() . ')' : ''}}
		</li>
@endforeach
</ul>
@endif

    

                </div>
            </div>
        </div>
    </div>
@endsection
