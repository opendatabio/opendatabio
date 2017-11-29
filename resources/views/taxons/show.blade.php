@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.taxon')
<div style="float:right;">
@if ($taxon->mobot)
<a href="http://tropicos.org/Name/{{$taxon->mobot}}"><img src="{{asset('images/TropicosLogo.gif')}}" alt="Tropicos"></a>
@endif
@if ($taxon->ipni)
<a href="http://www.ipni.org/ipni/idPlantNameSearch.do?id={{$taxon->ipni}}"><img src="{{asset('images/IpniLogo.png')}}" alt="IPNI" width="33px"></a>
@endif
@if ($taxon->mycobank)
<a href="http://www.mycobank.org/Biolomics.aspx?Table=Mycobank&Rec={{$taxon->mycobank}}&Fields=All"><img src="{{asset('images/MBLogo.png')}}" alt="Mycobank" width="33px"></a>
@endif
                </div>

		<div class="panel-body">
                    <!-- Display Validation Errors -->
		    <p><strong>
@lang('messages.name')
: </strong> <em> {{ $taxon->fullname }} </em></p>

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
<a href="{{ url('references/'.$bibref->id) }}">{{ $bibref->bibkey }} </a>
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

@if ($taxon->measurements()->count())
<div class="col-sm-6">
    <a href="{{ url('taxons/'. $taxon->id. '/measurements')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $taxon->measurements()->count() }}
@lang('messages.measurements')
    </a>
</div>
@else
    @can ('create', App\Measurement::class)
<div class="col-sm-6">
    <a href="{{ url('taxons/'. $taxon->id. '/measurements/create')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
@lang('messages.create_measurements')
    </a>
</div>
 @endcan
@endif
@if ($plants->count())
<div class="col-sm-6">
    <a href="{{ url('taxons/'. $taxon->id. '/plants')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $plants->count() }}
@lang('messages.plants')
    </a>
</div>
@endif
@if ($vouchers->count())
<div class="col-sm-6">
    <a href="{{ url('taxons/'. $taxon->id. '/vouchers')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $vouchers->count() }}
@lang('messages.vouchers')
    </a>
</div>
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
        {!! $taxon->senior->rawLink() !!}
        </p>
        @endif
        @if ($taxon->juniors->count())
        <p>
        @lang ('messages.juniors'):
        <ul>
        @foreach ($taxon->juniors as $junior)
        <li>{!! $junior->rawLink() !!}</li>
        @endforeach
        </ul>
        @endif
        @if ($taxon->getAncestors()->count())
        @foreach ($taxon->getAncestors() as $ancestor)
        {!! $ancestor->rawLink() !!} &gt;
        @endforeach
        @endif
        {{ $taxon->qualifiedFullname }}
        @if ($taxon->getDescendants()->count())
        <ul>
        @foreach ($taxon->children->sortBy('fullname') as $child)
        <li> {!! $child->rawLink() !!}
            {{ $child->getDescendants()->count() ? '(+' . $child->getDescendants()->count() . ')' : ''}}
        </li>
        @endforeach
        </ul>
        @endif
                </div>
            </div>
    </div>
@endsection
