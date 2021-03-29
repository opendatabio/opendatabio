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
        @lang('messages.hint_biocollections_page')
      </div>
    </div>
  </div>


@can ('create',App\Models\Biocollection::class)
            <div class="panel panel-default">
                <div class="panel-heading">
		                @lang('messages.new_biocollection')
                    <a data-toggle="collapse" href="#createbiocol" class="btn btn-default">?</a>
                </div>

                <div class="panel-body collapse" id='createbiocol'>
                    <!-- Display Validation Errors -->
		    @include('common.errors')

<div id="ajax-error" class="collapse alert alert-danger">
@lang('messages.whoops')
</div>
		    <form action="{{ url('biocollections')}}" method="POST" class="form-horizontal">
		    <input type="hidden" name="route-url" value="{{ route('checkih') }}">
		     {{ csrf_field() }}
		    @include('biocollections.form')
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-primary" id="checkih">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.checkih')

				</button>
				<div class="spinner" id="spinner"> </div>
				<div class="btn btn-link" id="noih" >
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.noih')
</div>
			    </div>
			</div>
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
@endcan

      <!-- Registered References -->
          <div class="panel panel-default">
              <div class="panel-heading">
                  @lang('messages.registered_biocollections')
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
$("#noih").click(
  function() {
    var currentclass = $(this).hasClass('btn-link');
    if (currentclass) {
        $("#checkih").removeClass("btn-link");
        $("#checkih").addClass("btn-link");
        $(this).removeClass("btn-link");
        $(this).addClass("btn-primary");
        $('#name').attr("readonly", false);
        $('#name').val(null);
        $('#irn').val(-1);
    } else {
        $("#checkih").removeClass("btn-link");
        $("#checkih").addClass("btn-primary");
        $(this).removeClass("btn-primary");
        $(this).addClass("btn-link");
        $('#name').attr("readonly", true);
        $('#name').val(null);
        $('#irn').val(null);
    }
  }
);
</script>
@endpush
