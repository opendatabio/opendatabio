@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.project')
		<div class="panel-body">
		    <p><strong>
@lang('messages.name')
: </strong>  {{ $project->name }} </p>

<p><strong>
@lang('messages.privacy')
:</strong>
@lang ('levels.privacy.' . $project->privacy)
</p>

<p><strong>
@lang('messages.admins')
:</strong>
<ul>
@foreach ($project->users()->wherePivot('access_level', '=', App\Project::ADMIN)->get() as $admin)
<li> {{ $admin->email }} </li>
@endforeach
</ul>
</p>

<p><strong>
@lang('messages.collaborators')
:</strong>
<ul>
@foreach ($project->users()->wherePivot('access_level', '=', App\Project::COLLABORATOR)->get() as $admin)
<li> {{ $admin->email }} </li>
@endforeach
</ul>
</p>

<p><strong>
@lang('messages.viewers')
:</strong>
<ul>
@foreach ($project->users()->wherePivot('access_level', '=', App\Project::VIEWER)->get() as $admin)
<li> {{ $admin->email }} </li>
@endforeach
</ul>
</p>

@if ($project->notes) 
		    <p><strong>
@lang('messages.notes')
: </strong> {{$project->notes}}
</p>
@endif

@can ('update', $project)
			    <div class="col-sm-6">
				<a href="{{ url('projects/'. $project->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>
            </div>
	@if ($project->plants()->count())
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.plants')
                </div>

                <div class="panel-body">
<ul>
@foreach ($project->plants as $plant)
<li> <a href="{{url('plants/' . $plant->id)}}">{{ $plant->fullname }} </a>
@if ($plant->identification)
(<em>{{$plant->identification->taxon->fullname}}</em>)
@else
    @lang ('messages.unidentified')
@endif
</li>
@endforeach
</ul>
                </div>
            </div>
	@endif
<!-- Other details (specialist, herbarium, collects, etc?) -->
    </div>
@endsection
