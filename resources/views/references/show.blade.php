@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.edit_reference')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

                    <!-- Edit Person Form -->
		    <form action="{{ url('references/'.$reference->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('PUT') }}
<div class="form-group">
    <label for="bibtex" class="col-sm-3 control-label">@lang('messages.bibtex_entry')</label>
    <div class="col-sm-9">
	<textarea name="bibtex" id="bibtex" class="form-control" rows=10 cols=80>{{ old('bibtex', isset($reference) ? $reference->bibtex : null) }}</textarea>
    </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>@lang('messages.save')
				</button>
			    </div>
			</div>
		    </form>
		    <form action="{{ url('references/'.$reference->id) }}" method="POST" class="form-horizontal">
			 {{ csrf_field() }}
                         {{ method_field('DELETE') }}
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-danger">
				    <i class="fa fa-btn fa-plus"></i>@lang('messages.remove_reference')</button>
			    </div>
			</div>
		    </form>
                </div>
            </div>
<!-- Other details (whatever links to a Reference) -->
        </div>
    </div>
@endsection
