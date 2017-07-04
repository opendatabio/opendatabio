@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.herbarium')
                </div>

		<div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')
		    <p><strong>
@lang('messages.acronym')
: </strong> {{ $herbarium->acronym }} </p>
		    <p><strong>
@lang('messages.institution')
: </strong> {{ $herbarium->name }} </p>
		    <p><a href="http://sweetgum.nybg.org/science/ih/herbarium_details.php?irn={{$herbarium->irn}}">
@lang('messages.details')
</a></p>
@can ('delete', $herbarium)
		    <form action="{{ url('herbaria/'.$herbarium->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('DELETE') }}
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.remove_herbarium')

				</button>
			    </div>
			</div>
		    </form>
@endcan
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.specialists')
                </div>

		<div class="panel-body">
                        <table class="table table-striped person-table">
                            <thead>
                                <th>
@lang('messages.abbreviation')
</th>
                                <th>
@lang('messages.name')
</th>
                                <th>
@lang('messages.email')
</th>
                            </thead>
                            <tbody>
                                @foreach ($herbarium->persons as $person)
                                    <tr>
					<td class="table-text"><div>
					<a href="{{ url('persons/'.$person->id) }}">{{ $person->abbreviation }}</a>
					</div></td>
                                        <td class="table-text">{{ $person->full_name }}</td>
                                        <td class="table-text">{{ $person->email }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
@endsection
