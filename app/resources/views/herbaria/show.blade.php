@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Herbarium
                </div>

		<div class="panel-body">
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
        </div>
    </div>
@endsection
