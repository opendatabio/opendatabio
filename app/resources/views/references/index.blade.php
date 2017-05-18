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

            <div class="panel panel-default">
                <div class="panel-heading">
                    Import References
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

		    <form action="{{ url('references')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
		     {{ csrf_field() }}

		        <div class="form-group">
			    <div class="col-sm-6">
  <span class="btn btn-success fileinput-button" id="fakerfile">
        <i class="glyphicon glyphicon-file"></i>
        <span>Import file</span>
  </span>
<input type="file" name="rfile" id="rfile" accept=".bib" style="display:none;">
<input type="hidden" name="MAX_FILE_SIZE" value="30000">
<button id="submit" type="submit" value="Submit" style="display: none;">Submeter!</button>
			    </div>
			</div>
		    </form>
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
