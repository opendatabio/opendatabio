@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">
@lang('messages.help')
</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.projects_hint')
      </div>
    </div>
  </div>

@can ('create', App\Project::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.create_project')
                </div>

                <div class="panel-body">
                <a href="{{url('projects/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

@if ($myprojects)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.my_projects')
                </div>

                <div class="panel-body">
<ul>
@foreach ($myprojects as $project)
    <li><a href="{{url('projects/' . $project->id)}}">{{$project->name}}</a>
(@lang('levels.project.' . $project->pivot->access_level )
)</li>
@endforeach
    </ul>
                </div>
            </div>
@endif

            <!-- Registered Projects -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.projects')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.name')
</th>
                                <th>
@lang('messages.members')
</th>
                                <th>
@lang('messages.data_privacy')
</th>
			    </thead>
<tbody>
                                @foreach ($projects as $project)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('projects/'.$project->id) }}">{{ $project->name }}</a>
					</td>
                                        <td class="table-text">
                                        @foreach ($project->users as $user)
                                        {{ $user->email }} <br/>
                                        @endforeach
                                        </td>
<td>
@lang ('levels.privacy.' . $project->privacy)
</td>
                                    </tr>
				    @endforeach
				    </tbody>
                        </table>
 {{ $projects->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection
