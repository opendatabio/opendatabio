@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
		    @can ('update', $person)
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.edit_person')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

		    <!-- Edit Person Form -->
		    <form action="{{ url('persons/'.$person->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('PUT') }}

			@include('persons.form')
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit_person')

				</button>
			    </div>
			</div>
		    </form>
			
			@can ('delete', $person)
		    <form action="{{ url('persons/'.$person->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('DELETE') }}
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.remove_person')

				</button>
			    </div>
			</div>
		    </form>
		    @endcan <!-- end can delete -->
                </div>
	    </div>
		    @else <!-- else can edit -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.person_details')
                </div>

		<div class="panel-body">
<div class="col-sm-6">
	<strong>
	@lang('messages.full_name')
	</strong>
</div>
<div class="col-sm-6">
	{{ $person->full_name }}
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.abbreviation')
	</strong>
</div>
<div class="col-sm-6">
	{{ $person->abbreviation }}
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.email')
	</strong>
</div>
<div class="col-sm-6">
	{{ $person->email }}
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.institution')
	</strong>
</div>
<div class="col-sm-6">

	{{ $person->institution }}
&nbsp;
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.herbarium')
	</strong>
</div>
<div class="col-sm-6">
@if ($person->herbarium)
<a href="{{url('herbaria/'. $person->herbarium->id)}}">{{ $person->herbarium->acronym }}</a>
@endif
&nbsp;
</div>
            </div>
		    @endcan <!-- end can edit -->
<!-- Other details (specialist, collects, etc?) -->
        </div>
    </div>
@endsection
