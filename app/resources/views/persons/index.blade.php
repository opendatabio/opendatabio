@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">Help</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
	This table represent people which may or may not be directly involved with the database. 
	It is used to store information about plant and voucher collectors, specialists, and database users. 
	When registering a new person, the system suggests the name abbreviation, but the user is free to change 
	it to better adapt it to the usual abbreviation used by each person. 
	The abbreviation should be unique for each person.
      </div>
    </div>
  </div>
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
