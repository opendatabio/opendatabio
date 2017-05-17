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
	This table contains the bibliographic references used when incorporating published data to the database.
	All references should be in Bibtex format - all major citation softwares are able to export to Bibtex format.
      </div>
    </div>
  </div>

            <!-- Registered References -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Bibliographic References
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <th>Bibtex Key</th>
                                <th>Authors</th>
                                <th>Year</th>
                                <th>Title</th>
                            </thead>
                            <tbody>
                                @foreach ($references as $reference)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('references/'.$reference->id) }}">{{ $reference->bibkey }}</a>
					</td>
                                        <td class="table-text">{{ $reference->author }}</td>
                                        <td class="table-text">{{ $reference->year }}</td>
                                        <td class="table-text">{{ $reference->title }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
			 {{ $references->links() }} 
                    </div>
                </div>
        </div>
    </div>
@endsection
