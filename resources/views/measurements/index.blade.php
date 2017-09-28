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
@lang('messages.measurements_hint')
      </div>
    </div>
  </div>

@can ('create', App\Measurement::class)
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.new_measurement')
                </div>

                <div class="panel-body">
			    <div class="col-sm-6">
				<a href="{{url ( $object->typename . '/' . $object->id .   '/measurements/create')}}" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create')
				</a>
			</div>
                </div>
	    </div>
@endcan
            <!-- Registered Vouchers -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.measurements')
                    </div>

                    <div class="panel-body">
<p><strong>
@lang('messages.object')
:</strong>
{{ $object->fullname }}
@if ($object->identification)
    (<em>{{ $object->identification->taxon->fullname }}</em>)
@endif
</p>
                        <table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.trait')
</th>
                                <th>
@lang('messages.value')
</th>
                                <th>
@lang('messages.unit')
</th>
                                <th>
@lang('messages.date')
</th>
			    </thead>
<tbody>
                                @foreach ($measurements as $measurement)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('traits/' . $measurement->trait_id) }}">{{ $measurement->odbtrait->name }}</a>
					</td>
					<td class="table-text">
					<a href="{{ url('measurements/'.$measurement->id) }}">{{ $measurement->valueActual }}</a>
					</td>
					<td class="table-text">
					{{ $measurement->odbtrait->unit}}
					</td>
					<td class="table-text">
					{{ $measurement->date}}
					</td>
                                    </tr>
				    @endforeach
				    </tbody>
                        </table>
 {{ $measurements->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection
