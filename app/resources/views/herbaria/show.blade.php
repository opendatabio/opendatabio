@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Herbarium
                </div>

		<div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')
		    <p><strong>Acronym: </strong> {{ $herbarium->acronym }} </p>
		    <p><strong>Institution name: </strong> {{ $herbarium->name }} </p>
		    <p><a href="http://sweetgum.nybg.org/science/ih/herbarium_details.php?irn={{$herbarium->irn}}">Details</a></p>
		    <form action="{{ url('herbaria/'.$herbarium->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('DELETE') }}
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>Delete Herbarium
				</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    Specialists
                </div>

		<div class="panel-body">
                        <table class="table table-striped person-table">
                            <thead>
                                <th>Abbreviation</th>
                                <th>Name</th>
                                <th>E-mail</th>
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
