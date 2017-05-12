@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    New Person
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

		    <form action="{{ url('persons')}}" method="POST" class="form-horizontal">
                     {{ csrf_field() }}
			@include('persons.form')
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>Add Person
				</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>

            <!-- Registered Persons -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Registered Persons
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped person-table">
                            <thead>
                                <th>Abbreviation</th>
                                <th>Name</th>
                                <th>E-mail</th>
                            </thead>
                            <tbody>
                                @foreach ($persons as $person)
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
			 {{ $persons->links() }} 
                    </div>
                </div>
        </div>
    </div>
@endsection
