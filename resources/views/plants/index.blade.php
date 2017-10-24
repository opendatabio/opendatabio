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
@lang('messages.plants_hint')
      </div>
    </div>
  </div>

@can ('create', App\Plant::class)
            <div class="panel panel-default">
                <div class="panel-heading">
      @lang('messages.create_plant')
                </div>

                <div class="panel-body">
			    <div class="col-sm-6">
				<a href="{{url ('plants/create')}}" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create')

				</a>
			</div>
                </div>
	    </div>
@endcan
            <!-- Registered Plants -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.plants')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.location_and_tag')
</th>
                                <th>
@lang('messages.identification')
</th>
			    </thead>
<tbody>
                                @foreach ($plants as $plant)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('plants/'.$plant->id) }}">{{ $plant->fullname }}</a>
					</td>
                                        <td class="table-text">
                                            @if ($plant->identification)
                                            <em>{{ $plant->identification->taxon->fullname }}</em>
                                            @else
                                            @lang('messages.unidentified')
                                            @endif
                                        </td>
                                    </tr>
				    @endforeach
				    </tbody>
                        </table>
 {{ $plants->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection
