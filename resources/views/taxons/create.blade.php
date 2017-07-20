@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">
@lang('messages.help')
</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.hint_taxon_create')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_taxon')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

<div id="ajax-error" class="collapse alert alert-danger">
@lang('messages.whoops')
</div>
@if (isset($taxon))
		    <form action="{{ url('taxons/' . $taxon->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('taxons')}}" method="POST" class="form-horizontal">
@endif
		    <input type="hidden" name="route-url" value="{{ route('checkapis') }}">
		     {{ csrf_field() }}
@include ('taxons.form')
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')

				</button>
			    </div>
			</div>
		    </form>
        </div>
    </div>
@endsection
