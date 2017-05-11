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

                    <!-- New Task Form -->
                    <form action="{{ url('persons')}}" method="POST" class="form-horizontal">
                        {{ csrf_field() }}

                        <!-- Task Name -->
                        <div class="form-group">
                            <label for="full_name" class="col-sm-3 control-label">Full Name</label>

                            <div class="col-sm-6">
                                <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="abbreviation" class="col-sm-3 control-label">Abbreviation</label>

                            <div class="col-sm-6">
                                <input type="text" name="abbreviation" id="abbreviation" class="form-control" value="{{ old('abbreviation') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">E-mail</label>

                            <div class="col-sm-6">
                                <input type="text" name="email" id="email" class="form-control" value="{{ old('email') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="institution" class="col-sm-3 control-label">Institution</label>

                            <div class="col-sm-6">
                                <input type="text" name="institution" id="institution" class="form-control" value="{{ old('institution') }}">
                            </div>
                        </div>

                        <!-- Add Task Button -->
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-6">
                                <button type="submit" class="btn btn-default">
                                    <i class="fa fa-btn fa-plus"></i>Add Person
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Tasks -->
            @if (count($persons) > 0)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Registered Persons
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped person-table">
                            <thead>
                                <th>Name</th>
                                <th>Abbreviation</th>
                                <th>&nbsp;</th>
                            </thead>
                            <tbody>
                                @foreach ($persons as $person)
                                    <tr>
					<td class="table-text"><div>
					<a href="{{ url('persons/'.$person->id) }}">{{ $person->full_name }}</a>
					</div></td>
                                        <td class="table-text"><div>{{ $person->abbreviation }}</div></td>

                                        <!-- Task Delete Button -->
                                        <td>
                                            <form action="{{ url('persons/'.$person->id) }}" method="POST">
                                                {{ csrf_field() }}
                                                {{ method_field('DELETE') }}

                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fa fa-btn fa-trash"></i>Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
			 {{ $persons->links() }} 
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
