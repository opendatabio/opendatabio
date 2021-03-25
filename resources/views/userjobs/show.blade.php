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
@else
<br>
<form action="{{ url('userjobs/'.$job->id) }}" method="POST">
{{ csrf_field() }}
{{ method_field('DELETE') }}
<button type="submit" class="btn btn-danger">
  <i class="glyphicon glyphicon-trash unstyle"></i> @lang ('messages.remove')
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
@if(is_array($item))
  <li> {{ serialize($item) }} </li>
@else
  @php
    //truncate item because otherwise becomes difficult to see large import recurds such as locations
    $item = (strlen($item) > 1000) ? substr($item, 0, 1000) . '... too long, truncated' : $item;
    $pattern = "/warning|file/i";
    if (preg_match($pattern, $item)) {
      $lineclass = 'alert alert-success';
    } else {
      $lineclass = 'alert alert-danger';
    }
  @endphp
  <li class="{{$lineclass}}" > {{!! $item !!}} </li>
@endif
@endforeach
				@endif
                </ul>
			</p>
                </div>
            </div>
        </div>
    </div>
@endsection
