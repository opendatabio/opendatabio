@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4 class="panel-title">
          @lang('messages.imports')
          <strong>
          @lang('messages.'.$model)
          </strong>
          &nbsp;&nbsp;&nbsp;&nbsp;<a data-toggle="collapse" href="#object_help" class="btn btn-default">?</a>
        </h4>
      </div>
      <div id="object_help" class="panel-body panel-collapse collapse">
       @php
         $txt = Lang::get('messages.import_'.$model.'_hint');
         if ( $txt != 'messages.import_'.$model.'_hint') {
           echo $txt;
         }
       @endphp
       <br><br>
       @lang('messages.imports_help')
       <hr>
     </div>
     <div class="panel-body">
       <!-- Display Validation Errors -->
       @include('common.errors')

        @php
        switch ($model) {
          case 'locations':
              $action = 'importLocations';
              break;
          case 'measurements':
              $action = 'importMeasurements';
              break;
          case 'individuals':
              $action = 'ImportIndividuals';
              break;
          case 'taxons':
              $action = 'importTaxons';
              break;
          case 'traits':
              $action = 'importTraits';
              break;
          case 'vouchers':
              $action = 'importVouchers';
              break;
          case 'persons':
              $action = 'importPersons';
              break;
          case 'biocollections':
              $action = 'ImportBiocollections';
              break;
          default:
            $action = null;
            break;
        }
        @endphp

      <form action="{{ url($action) }}" method="POST" class="form-horizontal" enctype="multipart/form-data">
        {{ csrf_field() }}
        <div class="form-group">
          <label for="data_file" class="col-sm-4 control-label mandatory">
            @lang('messages.import_file')
          </label>
          <div class="col-sm-6">
            <input type="file" name="data_file" id="data_file">
            <small>@lang('messages.import_file_hint')</small>
          </div>
        </div>

        @if ($model == 'traits')
          <div class="form-group">
            <label for="trait_categories_file" class="col-sm-4 control-label mandatory">
              @lang('messages.import_trait_categories_file')
            </label>
            <a data-toggle="collapse" href="#hint_trait_categories" class="btn btn-sm btn-default">?</a>
            <div class="col-sm-6">
              <input type="file" name="trait_categories_file" id="import_trait_categories_file">
              <small>@lang('messages.import_file_hint')</small>
            </div>
            <div class="col-sm-12">
              <div id="hint_trait_categories" class="panel-collapse collapse">
                @lang('messages.import_trait_categories_hint')
              </div>
            </div>
          </div>
        @endif


        <div class="form-group">
          <div class="col-sm-offset-3 col-sm-10">
            <button type="submit" class="btn btn-success" name="submit" value="submit">
              <i class="fa fa-btn fa-upload"></i>
              @lang('messages.submit')
            </button>
          </div>
        </div>
      </form>
  </div>
</div>

</div>
</div>
@endsection
