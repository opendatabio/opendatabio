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
        <a href=" {{url('taxons/' . $taxon->senior->id) }}"> {{ $taxon->senior->fullname }} </a>
        </p>
        @endif
        @if ($taxon->juniors->count())
        <p>
        @lang ('messages.juniors'):
        <ul>
        @foreach ($taxon->juniors as $junior)
        <li><a href=" {{ url('taxons/'. $junior->id ) }} ">{{ $junior->fullname }} </a> </li>
        @endforeach
        </ul>
        @endif
        @if ($taxon->getAncestors()->count())
        @foreach ($taxon->getAncestors() as $ancestor)
        <a href=" {{ url('taxons/'. $ancestor->id ) }} ">{{ $ancestor->fullname }} </a> &gt;
        @endforeach
        @endif
        {{ $taxon->fullname }}
        @if ($taxon->getDescendants()->count())
        <ul>
        @foreach ($taxon->children as $child)
        <li> <a href=" {{url('taxons/' . $child->id) }}"> <?php 
            if (!$child->valid) echo "**"; echo $child->fullname;
        ?> </a>
            {{ $child->getDescendants()->count() ? '(+' . $child->getDescendants()->count() . ')' : ''}}
        </li>
        @endforeach
        </ul>
        @endif
                </div>
            </div>
<!-- TODO!!! FIX FOR VOUCHERS -->
@if ($taxon->identifications()->count()) 

            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.plants')
                </div>

                <div class="panel-body">
<!-- TODO paginate?? -->
<table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.location_and_tag')
</th>
                </thead>
<tbody>
                                @foreach ($taxon->identifications as $id)
                                @if ($id->object)
                                    <tr>
                    <td class="table-text">
                    <a href="{{ url('plants/'.$id->object->id) }}">{{ $id->object->fullname }}</a>
                    </td>
                                    </tr>
                                @endif
                    @endforeach
                    </tbody>
                        </table>


                </div>
            </div>
@endif

    </div>
@endsection
