@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.dataset')
		<div class="panel-body">
		    <p><strong>
@lang('messages.name')
: </strong>  {{ $dataset->name }} </p>

<p><strong>
@lang('messages.privacy')
:</strong>
@lang ('levels.privacy.' . $dataset->privacy)
</p>

<p><strong>
@lang('messages.admins')
:</strong>
<ul>
@foreach ($dataset->users()->wherePivot('access_level', '=', App\Project::ADMIN)->get() as $admin)
<li> {{ $admin->email }} </li>
@endforeach
</ul>
</p>

<p><strong>
@lang('messages.collaborators')
:</strong>
<ul>
@foreach ($dataset->users()->wherePivot('access_level', '=', App\Project::COLLABORATOR)->get() as $admin)
<li> {{ $admin->email }} </li>
@endforeach
</ul>
</p>

<p><strong>
@lang('messages.viewers')
:</strong>
<ul>
@foreach ($dataset->users()->wherePivot('access_level', '=', App\Project::VIEWER)->get() as $admin)
<li> {{ $admin->email }} </li>
@endforeach
</ul>
</p>

@if ($dataset->notes) 
		    <p><strong>
@lang('messages.notes')
: </strong> {{$dataset->notes}}
</p>
@endif

@if ($dataset->bibreference_id) 
		    <p><strong>
@lang('messages.dataset_bibreference')
: </strong><a href="{{url('references/'.$dataset->bibreference_id)}}">{{$dataset->reference->bibkey}}</a>
</p>
@endif

@can ('update', $dataset)
			    <div class="col-sm-6">
				<a href="{{ url('datasets/'. $dataset->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>
            </div>
</div>
    </div>
@endsection
