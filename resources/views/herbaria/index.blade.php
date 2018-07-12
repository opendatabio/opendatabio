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
@lang('messages.hint_herbaria_page')
      </div>
    </div>
  </div>
@can ('create',App\Herbarium::class)
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_herbarium')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

<div id="ajax-error" class="collapse alert alert-danger">
@lang('messages.whoops')
</div>
		    <form action="{{ url('herbaria')}}" method="POST" class="form-horizontal">
		    <input type="hidden" name="route-url" value="{{ route('checkih') }}">
		     {{ csrf_field() }}
		    @include('herbaria.form')
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-primary" id="checkih">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.checkih')

				</button>
				<div class="spinner" id="spinner"> </div>
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
                        @lang('messages.registered_herbaria')
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
                                @foreach ($herbaria as $herbarium)
                                    <tr>
					<td class="table-text"><div>
                    {!! $herbarium->rawLink() !!}
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
