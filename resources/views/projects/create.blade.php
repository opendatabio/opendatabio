@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_project')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($project))
		    <form action="{{ url('projects/' . $project->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('projects')}}" method="POST" class="form-horizontal">
@endif
		     {{ csrf_field() }}
@include ('projects.form')
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')
				</button>
			    </div>
			</div>
		    </form>
        </div>
    </div>
@endsection
