@extends('layouts.app')

@section('content')
<div class="container">
    <div class="col-sm-offset-2 col-sm-8">

@can ('create', App\Models\BibReference::class)
    <div class="panel panel-default">

        <div class="panel-heading">
            @lang('messages.import_references')
            <button type='button' class="btn btn-default" id='importbutton'>?</button>
        </div>
        @if (count($errors) > 0)
          <div class="panel-body"  id="importbox" >
        @else
          <div class="panel-body"  id="importbox" hidden>
        @endif
          <div  class="form-group" >
              @lang('messages.references_hint')
          </div>

          <!-- Display Validation Errors -->
          @include('common.errors')

 <div class="form-group" >
   <br><br>

   <form action="{{ url('references')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
    {{ csrf_field() }}

   <div class="col-sm-3" style='float: left;'>
      <input type="checkbox" name="standardize" id="standardize" class="" checked >&nbsp;@lang('messages.standardize_keys')
      <br>
      <br>
      <span class="btn btn-success fileinput-button" id="fakerfile">
          <i class="glyphicon glyphicon-file"></i>
          <span>
              @lang('messages.import_file')
          </span>
      </span>
      <input type="file" name="rfile" id="rfile" accept=".bib" style="display:none;">
      <input type="hidden" name="MAX_FILE_SIZE" value="30000">
      <br><br><br>
      <input type="text" class='control-form' name="doi" placeholder='DOI here, ex. 10.1038/nrd842'>
      <br>
      <button id="checkdoi" type="button" class="btn btn-primary">@lang ('messages.doicheck')</button>
   </div>
  <div class="col-sm-7" style='float: right;'>
       <div class="spinner" id="spinner" hidden> </div>
       <div class="alert alert-danger" id='common_errors' hidden></div>
       <textarea id='bibtex' name="references" cols=35 rows=5 placeholder="paste here one or more bibtex records to import"></textarea>
       <br>
       <button id="submit" type="submit" value="Submit" class="btn btn-success">
           @lang ('messages.import_from_text')
       </button>
  </div>
</form>
</div>

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

<script>

$(document).ready(function() {

  $("#importbutton").on('click',function(){
    if ($("#importbox").is(":hidden")) {
      $("#importbox").show();
    } else {
      $("#importbox").hide();
    }
  });

  /** USED IN THE LOCATION MODAL TO SAVE A NEW Location*/
  $("#checkdoi").click(function(e) {
    $( "#spinner" ).css('display', 'inline-block');
    $.ajaxSetup({ // sends the cross-forgery token!
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
    e.preventDefault(); // does not allow the form to submit
    $.ajax({
      type: "POST",
      url: "{{ route('findbibtexfromdoi') }}",
      dataType: 'json',
            data: {
              'doi': $('input[name=doi]').val(),
            },
      success: function (data) {

        $( "#spinner" ).hide();
        var errors = data.errors;
        if (errors != null) {
          $( "#common_errors" ).show();
          var text = "<strong>" + "@lang ('messages.whoops')" + '</strong>here' + data.errors;
          $( "#common_errors" ).html(text);
        } else {
          var bibtex = data.bibtex;
          var curval = $("#bibtex").val();
          if (curval != null) {
            bibtex = bibtex + "\n" + curval;
          }
          $("#bibtex").val(bibtex);
        }
        //alert(data.errors);
      },
      error: function(e){
        $( "#spinner" ).hide();
        $( "#common_errors" ).show();
        var text = "<strong>" + "@lang ('messages.whoops')" + '</strong>' + e;
        $( "#common_errors" ).html(text);
      }
    })
  });




});

</script>


@endpush
