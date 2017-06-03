@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.monitor_userjob')
                </div>

                <div class="panel-body">
			<p><strong>@lang('messages.jobid'): {{ $job->id }} </strong></p>
			<p><strong>@lang('messages.type'): </strong> {{ $job->dispatcher }} </p>
			<p><strong>@lang('messages.status'): </strong> {{ $job->status }} </p>
			<p><strong>@lang('messages.log'): </strong><br>
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
