@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">@lang('messages.help')</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.userjobs_hint')
      </div>
    </div>
  </div>

            <!-- Registered Persons -->
                <div class="panel panel-default">
                    <div class="panel-heading">
			@lang('messages.registered_userjobs')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped user-table">
                            <thead>
                                <th>@lang('messages.id')</th>
                                <th>@lang('messages.status')</th>
                                <th>@lang('messages.created_at')</th>
                                <th>@lang('messages.updated_at')</th>
                                <th colspan=2>@lang('messages.actions')</th>
                            </thead>
                            <tbody>
                                @foreach ($jobs as $job)
                                    <tr>
					<td class="table-text"><div>
					<a href="{{ url('userjobs/'.$job->id) }}">{{ $job->id }}</a>
					</div></td>
                                        <td class="table-text">{{ $job->status }}</td>
                                        <td class="table-text">{{ $job->created_at }}</td>
                                        <td class="table-text">{{ $job->updated_at }}</td>
					<td class="table-text">
					@if ($job->status == 'Failed' or $job->status == 'Cancelled')
<form action="{{ url('userjobs/'.$job->id.'/retry') }}" method="POST">
{{ csrf_field() }}
<button type="submit" class="glyphicon glyphicon-repeat unstyle" title="@lang('messages.retry')">
</button>
</form>
					@endif
					@if ($job->status == 'Submitted' or $job->status == 'Processing')
<form action="{{ url('userjobs/'.$job->id.'/cancel') }}" method="POST">
{{ csrf_field() }}
<button type="submit" class="glyphicon glyphicon-remove unstyle" title="@lang('messages.cancel')">
</button>
</form>
					@endif
					</td>
					<td class="table-text">
<form action="{{ url('userjobs/'.$job->id) }}" method="POST">
{{ csrf_field() }}
{{ method_field('DELETE') }}
<button type="submit" class="glyphicon glyphicon-trash unstyle" title="@lang('messages.remove')">
</button>
</form>
					</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
			 {{ $jobs->links() }} 
                    </div>
                </div>
        </div>
    </div>
@endsection
