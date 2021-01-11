@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">
      <div class="panel-heading">
        @lang('messages.project')
        <strong>
          {{ $project->name }}
        </strong>
        <span class="history" style="float:right">
        <a href="{{url("projects/$project->id/activity")}}">
        @lang ('messages.see_history')
        </a>
        </span>
      </div>
      <div class="panel-body">



<div class="col-sm-12">
  <div class="col-sm-9">
    @if ($project->description)
      <p>
        {{$project->description}}
      </p>
    @endif

    <p>
      <strong>
        @lang('messages.privacy')
      </strong>
      :
      @lang ('levels.privacy.' . $project->privacy)
    </p>

    @if ($project->tags)
      <p><strong>
        @lang('messages.tagged_with')
        : </strong> {!! $project->tagLinks !!}
      </p>
    @endif

    @if ($project->url)
      <p>
        <strong>
          URL
        </strong>
        :
        <a href="{!! $project->url !!}">{{ $project->url }}</a>
      </p>
    @endif
  </div>



  <div class="col-sm-2">
    @if(isset($logo))
      <div class="float-right">
        <img src='{{ url($logo) }}' width='150'>
      </div>
    @endif
  </div>
</div>



<!-- SUMMARY BUTTONS -->
<div class="col-sm-12">
  <br><br>
  <a data-toggle="collapse" href="#project_people" class="btn btn-default">@lang('messages.persons')</a>

  &nbsp;&nbsp;
  <a href="{{ url('datasets/'. $project->id. '/project')  }}" class="btn btn-default">
    <i class="fa fa-btn fa-search"></i>
    {{ $project->getCount('all',null,'datasets') }}
    @lang('messages.datasets')
  </a>

  @can('view_details',$project)
    @if(isset($project->details))
      &nbsp;&nbsp;
      <a data-toggle="collapse" href="#project_details" class="btn btn-default">@lang('messages.project_details')</a>
    @endif
  @endcan
</div>


  <!-- RELATED MODELS BUTTONS -->
  <div class="col-sm-12">
  <br>
    @if ($project->getCount('all',null,'vouchers'))
    <a href="{{ url('projects/'. $project->id. '/vouchers')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
      {{ $project->getCount('all',null,'vouchers') }}
      @lang('messages.vouchers')
    </a>
    &nbsp;&nbsp;
    @endif
    @if ($project->getCount('all',null,'plants'))
      <a href="{{ url('projects/'. $project->id. '/plants')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
      {{ $project->getCount('all',null,'plants') }}
      @lang('messages.plants')
    </a>
    @endif
    &nbsp;&nbsp;
    <a href="{{ url('projects/'. $project->id. '/taxons')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
      {{ $project->taxonsCount() }}
      @lang('messages.taxons')
    </a>
    &nbsp;&nbsp;
    <a href="{{ url('locations/'. $project->id. '/project')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
      {{ $project->getCount('all',null,'locations') }}
      @lang('messages.locations')
    </a>
  </div>

  <div class="col-sm-12">
  <br>
  <button id='summary_button' type="button" class="btn btn-default">
    <span id='summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
    @lang('messages.summary')
  </button>
  &nbsp;&nbsp;
  <button id='identifications_summary_button' type="button" class="btn btn-default">
    <span id='identifications_summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
    @lang('messages.identifications_summary')
  </button>
  <!--- <?php // TODO: requires a faster summary  ?>
  &nbsp;&nbsp;
  <button id='taxonomic_summary_button' type="button" class="btn btn-default">
    <span id='taxonomic_summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
  </button>
  --->
  </div>

@can ('update', $project)
  <div class="col-sm-12">
    <br>
    <a href="{{ url('projects/'. $project->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
      @lang('messages.edit')
    </a>
  </div>
@endcan


</div>
</div>

<!-- START people  BLOCK -->
<div class="panel panel-default panel-collapse collapse" id='project_details'>
  <div class="panel-body">
    {{ $project->details }}
  </div>
</div>


<!-- START people  BLOCK -->
  <div class="panel panel-default panel-collapse collapse" id='project_people'>
    <div class="panel-heading">
      <strong>
        @lang('messages.persons')
      </strong>
    </div>
    <div class="panel-body">
      <p><strong>@lang('messages.admins'): </strong>
        <ul>
          @foreach ($project->users()->wherePivot('access_level', '=', App\Project::ADMIN)->get() as $admin)
            <li> {{ $admin->email }} </li>
          @endforeach
        </ul>
      </p>
      <p><strong>@lang('messages.collaborators'):</strong>
        <ul>
          @foreach ($project->users()->wherePivot('access_level', '=', App\Project::COLLABORATOR)->get() as $admin)
            <li> {{ $admin->email }} </li>
          @endforeach
        </ul>
      </p>
      <p><strong>@lang('messages.viewers'):</strong>
        <ul>
          @foreach ($project->users()->wherePivot('access_level', '=', App\Project::VIEWER)->get() as $admin)
            <li> {{ $admin->email }} </li>
          @endforeach
        </ul>
      </p>
</div>
</div>


<!-- START summary BLOCK -->
<div class="panel panel-default" id='project_summary' hidden></div>
<div class="panel panel-default" id='project_identification_block' hidden></div>
<!-- end -->

</div>



</div>
<input type="hidden" id='project_id' value="{{ $project->id }}" >
@endsection
@push ('scripts')

<script>

$(document).ready(function() {

  /* summarize project */
  $('#summary_button').on('click', function(e){
      if ($('#project_summary').is(':empty')){
        $('#summary_loading').show();
        e.preventDefault();
        $.ajax({
          type: 'POST',
          url: "{{ route('projectsummary',$project->id) }}",
          data: {
            "id" : "{{ $project->id }}",
            "_token" : "{{ csrf_token() }}"
          },
          success: function(data) {
              $('#project_summary').html(data);
              $('#project_summary').show();
              $('#summary_loading').hide();
          },
          error: function(data) {
              $('#project_summary').html("NOT FOUND");
              $('#project_summary').show();
              $('#summary_loading').hide();
          }
        });
      } else {
        if ($('#project_summary').is(':visible')) {
          $('#project_summary').hide();
        } else {
          $('#project_summary').show();
        }
      }
  });

 /* summarize identifications */
  $('#identifications_summary_button').on('click', function(e){
    if ($('#project_identification_block').is(':empty')){
      $('#identifications_summary_loading').show();
      e.preventDefault();
      $.ajax({
        type: 'POST',
        url: "{{ route('project_identification_summary',$project->id) }}",
        data: {
          "id" : "{{ $project->id }}",
          "_token" : "{{ csrf_token() }}"
        },
        success: function(data) {
            $('#project_identification_block').html(data);
            $('#project_identification_block').show();
            $('#identifications_summary_loading').hide();
            $('#project_identification_block').focus();
        },
        error: function(data) {
            $('#project_identification_block').html("NOT FOUND");
            $('#project_identification_block').show();
            $('#identifications_summary_loading').hide();
        }
      });
  } else {
    if ($('#project_identification_block').is(':visible')) {
      $('#project_identification_block').hide();
    } else {
      $('#project_identification_block').show();
    }
  }
  });

});

</script>
@endpush
