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
	This table represent the system users. Each user has a valid e-mail and password, and an access level, which
	determines the actions he or she may take on the system. This table does not store information about 
	plant or voucher collectors or specialists. Use the "Persons" tab for that.
      </div>
    </div>
  </div>

            <!-- Registered Persons -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Registered Persons
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped user-table">
                            <thead>
                                <th>E-mail</th>
                                <th>Access level</th>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
					<td class="table-text"><div>
					<a href="{{ url('users/'.$user->id) }}">{{ $user->email }}</a>
					</div></td>
                                        <!--td class="table-text">{{ $user->full_name }}</td-->
                                        <td class="table-text">To be implemented...</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
			 {{ $users->links() }} 
                    </div>
                </div>
        </div>
    </div>
@endsection
