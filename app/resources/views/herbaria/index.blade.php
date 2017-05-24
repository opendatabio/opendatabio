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
	This table contains the registered herbaria in which the vouchers can be stored. All herbaria should have
	an identification number from the Index Herbariorum, which can be used to retrieve other details such as
	address, phone, or e-mail. You can register herbarias using the acronym (also called Herbarium Code), which
	normally consists on two to five letters. All other fields will be filled in automatically.
      </div>
    </div>
  </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    Register Herbarium
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

<div id="ajax-error" class="collapse alert alert-danger">Error!</div>
		    <form action="{{ url('herbaria')}}" method="POST" class="form-horizontal">
		    <input type="hidden" name="route-url" value="{{ route('checkih') }}">
		     {{ csrf_field() }}
		    @include('herbaria.form')
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-primary" id="checkih">
				    <i class="fa fa-btn fa-plus"></i>Check Index Herbariorum
				</button>
				<div class="spinner" id="spinner"> </div>
			    </div>
			</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>Add Herbarium
				</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        Registered Herbaria
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped person-table">
                            <thead>
                                <th>Acronym</th>
                                <th>Institution</th>
                                <th>Details</th>
                            </thead>
                            <tbody>
                                @foreach ($herbaria as $herbarium)
                                    <tr>
					<td class="table-text"><div>
					<a href="{{ url('herbaria/'.$herbarium->id) }}">{{ $herbarium->acronym }}</a>
					</div></td>
                                        <td class="table-text">{{ $herbarium->name }}</td>
					<td class="table-text">
					<a href="http://sweetgum.nybg.org/science/ih/herbarium_details.php?irn={{$herbarium->irn}}">Details</a>
					</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
			 {{ $herbaria->links() }} 
                    </div>
                </div>
        </div>
    </div>
@endsection
