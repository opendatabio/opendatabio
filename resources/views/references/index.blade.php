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
@lang('messages.references_hint')
      </div>
    </div>
  </div>

@can ('create', App\BibReference::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.import_references')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

<form action="{{ url('references')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
 {{ csrf_field() }}

    <div class="form-group">
        <div class="col-sm-4" style="float: left">
            <label for="standardize" class="control-label">
                <input type="checkbox" name="standardize" id="standardize" class="" checked >
                @lang('messages.standardize_keys')
            </label>
        </div>
        <div class="col-sm-7" style="float: right">
            <textarea name="references" cols=35></textarea>
        </div>

        <div class="col-sm-4" style="float: left">
            <span class="btn btn-success fileinput-button" id="fakerfile">
                <i class="glyphicon glyphicon-file"></i>
                <span>
                    @lang('messages.import_file')
                </span>
            </span>
            <input type="file" name="rfile" id="rfile" accept=".bib" style="display:none;">
            <input type="hidden" name="MAX_FILE_SIZE" value="30000">
        </div>
        <div class="col-sm-7" style="float: right">
            <button id="submit" type="submit" value="Submit" class="btn btn-success">
                @lang ('messages.import_from_text')
            </button>
        </div>
    </div>
</form>
                </div>
            </div>
@endcan
            <!-- Registered References -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.bibliographic_references')
                    </div>
                    <div class="panel-body">
{!! $dataTable->table() !!}
                </div>
                </div>
        </div>
    </div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
