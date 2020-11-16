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
                  <a data-toggle="collapse" href="#myprojects" class="btn btn-default">@lang('messages.my_projects')</a>
                </div>
                <div class="panel-collapse collapse" id='myprojects'>
                  <br>
<ul>
@foreach ($myprojects as $project)

    <li>{!! $project->rawLink() !!}
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
{!! $dataTable->table() !!}
                    </div>
                </div>
        </div>
    </div>
@endsection

@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
