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


@if ($project->vouchers()->count())
<div class="col-sm-3">
    <a href="{{ url('projects/'. $project->id. '/vouchers')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $project->vouchers()->count() }}
@lang('messages.vouchers')
    </a>
</div>
@else
@can ('create', App\Voucher::class)
<div class="col-sm-4">
<a href="{{url ('projects/' . $project->id . '/vouchers/create')}}" class="btn btn-default">
    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_voucher')
</a>
</div>
@endcan
@endif

@if ($project->plants()->count())
<div class="col-sm-3">
    <a href="{{ url('projects/'. $project->id. '/plants')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $project->plants()->count() }}
@lang('messages.plants')
    </a>
</div>
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
<!-- Other details (specialist, herbarium, collects, etc?) -->
    </div>
@endsection
