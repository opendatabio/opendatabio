@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.taxon')
<div style="float:right;">
@if ($taxon->mobot()->count())
<a href="http://tropicos.org/Name/{{$taxon->mobot()->first()->reference}}"><img src="{{asset('images/TropicosLogo.gif')}}" alt="Tropicos"></a>
@endif
                </div>

		<div class="panel-body">
                    <!-- Display Validation Errors -->
		    <p><strong>
@lang('messages.name')
: </strong> <em> {{ $taxon->name }} </em></p>

<p><strong>
@lang('messages.author')
:</strong>
@if ($author)
<a href="{{ url('persons/'.$author->id) }}">{{ $author->abbreviation }} </a>
@else
{{ $taxon->author }} 
@endif
</p>

@if ( $bibref or $taxon->bibreference )
<p><strong>
@lang('messages.bibreference')
:</strong>
@if ($bibref)
<a href="{{ url('bibreferences/'.$bibref->id) }}">{{ $bibref->bibkey }} </a>
@else
{{ $taxon->bibreference }} 
@endif
</p>
@endif

		    <p><strong>
@lang('messages.level')
: </strong> 
@lang ('levels.tax.' . $taxon->level)
</p>


<p><strong>
@lang('messages.valid_status')
: </strong> 
@if ($taxon->valid) 
@lang ('messages.isvalid')
@else
        @lang ('messages.notvalid')
@endif
</p>

@if ($taxon->persons->count())
<p><strong>
@lang('messages.specialists')
: </strong> 
<ul>
@foreach ($taxon->persons as $person)
<li><a href="{{ url('persons/' . $person->id ) }}">{{ $person->full_name }}</a></li>
@endforeach
</ul>
</p>
@endif

@if ($taxon->notes) 
		    <p><strong>
@lang('messages.notes')
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
        @if ($taxon->senior)
        <p>
        @lang ('messages.accepted_name'):
        <a href=" {{url('taxons/' . $taxon->senior->id) }}"> {{ $taxon->senior->name }} </a>
        </p>
        @endif
        @if ($taxon->juniors->count())
        <p>
        @lang ('messages.juniors'):
        <ul>
        @foreach ($taxon->juniors as $junior)
        <li><a href=" {{ url('taxons/'. $junior->id ) }} ">{{ $junior->name }} </a> </li>
        @endforeach
        </ul>
        @endif
        @if ($taxon->getAncestors()->count())
        @foreach ($taxon->getAncestors() as $ancestor)
        <a href=" {{ url('taxons/'. $ancestor->id ) }} ">{{ $ancestor->name }} </a> &gt;
        @endforeach
        @endif
        {{ $taxon->name }}
        @if ($taxon->getDescendants()->count())
        <ul>
        @foreach ($taxon->children as $child)
        <li> <a href=" {{url('taxons/' . $child->id) }}"> <?php 
            if (!$child->valid) echo "**"; echo $child->name;
        ?> </a>
            {{ $child->getDescendants()->count() ? '(+' . $child->getDescendants()->count() . ')' : ''}}
        </li>
        @endforeach
        </ul>
        @endif
        
    

                </div>
            </div>
    </div>
@endsection
