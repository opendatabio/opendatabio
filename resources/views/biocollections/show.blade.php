@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.biocollection')
                </div>

		<div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')
		    <p><strong>
@lang('messages.acronym')
: </strong> {{ $biocollection->acronym }} </p>
		    <p><strong>
@lang('messages.institution')
: </strong> {{ $biocollection->name }} </p>
		    <p>
          @if($biocollection->irn >0)
          <a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn={{$biocollection->irn}}">
@lang('messages.details')
</a>
          @else
          @lang('messages.noih')
          @endif
</p>

@if($biocollection->vouchers()->count() == 0)
    @can ('delete', $biocollection)
    		    <form action="{{ url('biocollections/'.$biocollection->id) }}" method="POST" class="form-horizontal">
    			 {{ csrf_field() }}
           {{ method_field('DELETE') }}
    		        <div class="form-group">
    	           <div class="col-sm-offset-3 col-sm-6">
                   <button type="submit" class="btn btn-danger">
                     <i class="fa fa-btn fa-plus"></i>
                     @lang('messages.remove_biocollection')
                   </button>
                 </div>
               </div>
    		    </form>
    @endcan
@endif
                </div>
            </div>
            <!-- Other details (specialist, biocollection, collects, etc?) -->
            @if ($biocollection->persons->count())
              <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.specialists')
                </div>
		            <div class="panel-body">
                  <table class="table table-striped person-table">
                    <thead>
                      <th>
                        @lang('messages.abbreviation')
                      </th>
                      <th>
                        @lang('messages.name')
                      </th>
                      <th>
                        @lang('messages.email')
                      </th>
                    </thead>
                    <tbody>
                      @foreach ($biocollection->persons as $person)
                        <tr>
                          <td class="table-text">
                            <div>
                              {!! $person->rawLink() !!}
                            </div>
                          </td>
                          <td class="table-text">
                            {{ $person->full_name }}
                          </td>
                          <td class="table-text">
                            {{ $person->email }}
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif



            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.vouchers')
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
