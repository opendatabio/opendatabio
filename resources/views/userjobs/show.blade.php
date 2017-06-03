@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.monitor_userjob')
                </div>

                <div class="panel-body">
			<p><strong>Job id: {{ $job->id }} </strong></p>
			<p><strong>Type: </strong> {{ $job->dispatcher }} </p>
			<p><strong>Status: </strong> {{ $job->status }} </p>
			<p><strong>Completion: </strong> {{ $job->complete }} </p>
			<p><strong>Log: </strong><br>
				@if (empty($job->log))
					-null-
				@else
					{{ $job->log }} 
				@endif
			</p>
                </div>
            </div>
        </div>
    </div>
@endsection
