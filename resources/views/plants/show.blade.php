@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.plant')
		<div class="panel-body">
		    <p><strong>
@lang('messages.location')
: </strong>  
<a href="{{url('locations/' . $plant->location->id)}}">{{$plant->location->name}}</a>
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
{{$plant->date}}
</p>

@if ($plant->notes)
<p><strong>
@lang('messages.notes')
:</strong>
{{$plant->notes}}
</p>
@endif

@can ('update', $plant)
			    <div class="col-sm-6">
				<a href="{{ url('plant/'. $plant->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
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
