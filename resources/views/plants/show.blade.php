@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.plant')
                </div>
		<div class="panel-body">
<p><strong>
@lang('messages.identification')
: </strong>
@if (is_null($identification))
        @lang ('messages.unidentified')
@else
    <em>        
    <a href="{{url('taxons/' . $identification->taxon->id)}}">
        {{ $identification->taxon->fullname }}
    </a>
    </em>
    @if ($identification->modifier)
        @lang ('levels.identification.' . $identification->modifier)
    @endif
    </p>
    <p><strong>
    @lang('messages.identified_by')
:</strong>
    <a href="{{url('persons/' . $identification->person->id)}}">
        {{ $identification->person->full_name }}
    </a> ({{ $identification->formatDate }})
    </p>
    @if ($identification->herbarium_id)
    <p><strong>
    @lang('messages.identification_based_on')
:</strong>
    <a href="{{url('herbaria/' . $identification->herbarium_id)}}">
        {{ $identification->herbarium->acronym }}
    </a>
    </p>
    @endif
    @if ($identification->notes)
    <p><strong>
    @lang('messages.identification_notes')
:</strong>
        {{ $identification->notes }}
    </a> 
    </p>
    @endif


@endif
        
<p><strong>
@lang('messages.location')
: </strong>  
<a href="{{url('locations/' . $plant->location->id)}}">{{$plant->location->name}}</a>
@if ($plant->x)
(X: {{$plant->x}} Y: {{$plant->y}})
@endif
</p>

<p><strong>
@lang('messages.plant_tag')
:</strong>
{{ $plant->tag }}
</p>

<p><strong>
@lang('messages.project')
:</strong>
<a href="{{url('projects/' . $plant->project->id)}}">{{$plant->project->name}}</a>
</p>

<p><strong>
@lang('messages.collection_date')
:</strong>
{{$plant->formatDate}}
</p>

@if ($plant->notes)
<p><strong>
@lang('messages.notes')
:</strong>
{{$plant->notes}}
</p>
@endif

<p><strong>
@lang('messages.collectors')
:</strong>
@if ($collectors->count())
    <ul>
    @foreach ($collectors as $collector)
    <li><a href="{{url('persons/' . $collector->person->id)}}">{{$collector->person->full_name}}</a></li>
    @endforeach
    </ul>
@else
    @lang('messages.not_registered')
@endif
</p>


@can ('update', $plant)
			    <div class="col-sm-6">
				<a href="{{ url('plants/'. $plant->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>
            </div>

            @if ($plant->vouchers->count())
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.vouchers')
                </div>
		<div class="panel-body">
        <ul>
        @foreach ($plant->vouchers as $voucher) 
            <li><a href="{{url('vouchers/'. $voucher->id)}}">{{$voucher->fullname}}</a></li>
        @endforeach
        </ul>
        </div>
            </div>
            @endif

<!-- Other details (specialist, herbarium, collects, etc?) -->
    </div>
@endsection
