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
@can ('create',App\Biocollection::class)
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_biocollection')
                </div>

                <div class="panel-body">
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
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.registered_biocollections')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped person-table">
                            <thead>
                                <th>
@lang('messages.acronym')
</th>
                                <th>
@lang('messages.institution')
</th>
                                <th>
@lang('messages.details')
</th>
                            </thead>
                            <tbody>
                                @foreach ($biocollections as $biocollection)
                                    <tr>
					<td class="table-text"><div>
                    {!! $biocollection->rawLink() !!}
					</div></td>
                                        <td class="table-text">{{ $biocollection->name }}</td>
					<td class="table-text">
          @if($biocollection->irn >0)
					<a href="http://sweetgum.nybg.org/science/ih/biocollection_details.php?irn={{$biocollection->irn}}">Details</a>
          @else
          @lang('messages.noih')
          @endif
					</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
			 {{ $biocollections->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection

@push ('scripts')
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
