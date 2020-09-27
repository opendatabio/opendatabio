@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.monitor_userjob')
                </div>

                <div class="panel-body">
			<p><strong>
@lang('messages.jobid')
: {{ $job->id }} </strong></p>
			<p><strong>
@lang('messages.type')
: </strong> {{ $job->dispatcher }} </p>
<p><strong>
@lang('messages.actions')
:</strong>
					@if ($job->status == 'Failed' or $job->status == 'Cancelled')
<form action="{{ url('userjobs/'.$job->id.'/retry') }}" method="POST">
{{ csrf_field() }}
<button type="submit" class="btn btn-success">
    <i class="glyphicon glyphicon-repeat"></i> @lang ('messages.retry')
</button>
</form>
					@endif
					@if ($job->status == 'Submitted' or $job->status == 'Processing')
<form action="{{ url('userjobs/'.$job->id.'/cancel') }}" method="POST">
{{ csrf_field() }}
<button type="submit" class="btn btn-warning">
    <i class="glyphicon glyphicon-remove"></i> @lang ('messages.cancel')
</button>
</form>
					@endif
                    </p>

			<p><strong>
@lang('messages.status')
: </strong> {{ $job->status }} ({{ $job->percentage }})</p>
			<p><strong>
@lang('messages.log')
: </strong><br>
				@if (empty($job->log))
					-null-
                    @else
                        <ul>
@foreach (json_decode($job->log, true) as $item)
<li> {{ serialize($item) }} </li>
@endforeach
				@endif
                </ul>
			</p>
                </div>
            </div>
        </div>
    </div>
@endsection
