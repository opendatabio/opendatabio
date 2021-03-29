@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="col-sm-offset-2 col-sm-8">


            <!-- Registered Projects -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.projects')
                        &nbsp;&nbsp;
                        <a data-toggle="collapse" href="#help" class="btn btn-default">@lang('messages.help')</a>
                        @if ($myprojects)
                          &nbsp;&nbsp;
                          <a data-toggle="collapse" href="#myprojects" class="btn btn-default">@lang('messages.my_projects')</a>
                        @endif
                        @can ('create', App\Models\Project::class)
                          &nbsp;&nbsp;
                          <a href="{{url('projects/create')}}" class="btn btn-success">
                            @lang ('messages.create')
                          </a>
                        @endcan
                  </div>
                  @if ($myprojects)
                  <div class="panel-body collapse" id='myprojects'>
                    <br>
                    <ul>
                      @foreach ($myprojects as $project)
                        <li>
                          {!! $project->rawLink() !!}
                          (
                            @lang('levels.project.' . $project->pivot->access_level )
                          )
                        </li>
                      @endforeach
                    </ul>
                  </div>
                  @endif
                    <div id="help" class="panel-body collapse">
                        @lang('messages.projects_hint')
                    </div>
                    <br>
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

  var table = $('#dataTableBuilder').DataTable();

  $('tbody').on('click', 'tr',function () {
      //console.log( table.row( this ).data() );
      var id =  table.row( this ).data().id;
      var id = 'description_'+id;
      if($('#'+id).is(':hidden')) {
        $('#'+id).show();
      } else {
        $('#'+id).hide();
      }
  });


});


</script>

@endpush
