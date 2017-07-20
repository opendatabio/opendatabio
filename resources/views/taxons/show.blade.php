@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.taxon')
                </div>

		<div class="panel-body">
                    <!-- Display Validation Errors -->
		    <p><strong>
@lang('messages.name_and_author')
: </strong> <em> {{ $taxon->name }} </em> {{ $taxon->author }} </p>
		    <p><strong>
@lang('messages.level')
: </strong> 
@lang ('levels.tax.' . $taxon->level)
</p>
@if ($taxon->notes) 
		    <p><strong>
@lang('messages.level')
: </strong> {{$taxon->notes}}
</p>
@endif
@can ('update', $taxon)
			    <div class="col-sm-6">
				<a href="{{ url('taxons/'. $taxon->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.taxon_ancestors_and_children')
                </div>

                <div class="panel-body">
	@if ($taxon->getAncestors()->count())
	@foreach ($taxon->getAncestors() as $ancestor)
		<a href=" {{ url('taxons/'. $ancestor->id ) }} ">{{ $ancestor->name }} </a> &gt;
	@endforeach
	@endif
	 {{ $taxon->name }}
	 @if ($taxon->getDescendants()->count())
	<ul>
	 @foreach ($taxon->children as $child)
		<li> <a href=" {{url('taxons/' . $child->id) }}"> {{ $child->name }} </a>
			{{ $child->getDescendants()->count() ? '(+' . $child->getDescendants()->count() . ')' : ''}}
		</li>
@endforeach
</ul>
@endif

    

                </div>
            </div>
    </div>
@endsection
