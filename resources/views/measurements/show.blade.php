@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.measurement')
		<div class="panel-body">
<p><strong>
@lang('messages.object')
: </strong>
{{ $measurement->measured->fullname }}
@if ($measurement->measured->identification)
    (<em>{{ $measurement->measured->identification->taxon->fullname }}</em>)
@endif

        
<p><strong>
@lang('messages.trait')
: </strong>  
<a href="{{url('traits/' . $measurement->trait_id)}}">{{$measurement->odbtrait->name}}</a>
<br><em>  
{{$measurement->odbtrait->description}}</em>
</p>
<!-- TODO! integrate with mix -->
<style> 
.measurement-thumb {
	width: 16px;
	height: 16px;
	margin: 2px 1px;
	display: inline-block;
	border: solid 1px #d0d0d0;
}
</style>
<p><strong>
@lang('messages.value')
: </strong>  
{{$measurement->valueActual}} {{ $measurement->odbtrait->unit }}
@if ($measurement->type == \App\ODBTrait::COLOR) 
&nbsp;<span class="measurement-thumb" style="background-color: {{$measurement->valueActual}}">
@endif
</p>


<p><strong>
@lang('messages.dataset')
:</strong>
<a href="{{url('datasets/' . $measurement->dataset_id)}}">{{$measurement->dataset->name}}</a>
</p>

<p><strong>
@lang('messages.measurement_date')
:</strong>
{{$measurement->formatDate}}
</p>

@if ($measurement->person)
<p><strong>
@lang('messages.measurement_measurer')
:</strong>
<a href="{{url('persons/' . $measurement->person_id)}}">{{$measurement->person->full_name}}</a>
</p>
@endif

@if ($measurement->bibreference)
<p><strong>
@lang('messages.reference')
:</strong>
<a href="{{url('references/' . $measurement->bibreference_id)}}">{{$measurement->bibreference->bibkey}}</a>
</p>
@endif

@can ('update', $measurement)
			    <div class="col-sm-6">
				<a href="{{ url('measurements/'. $measurement->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
    </div>
@endsection
