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

        <!--- DEFINE LOCK IMAGES BASED ON PRIVACY OF DATA -->
        <!-- DEFINE LICENSE IMAGE BASED ON LICENSE -->
        @php
          $lockimage= '<i class="fas fa-lock"></i>';
          if ($project->privacy != App\Project::PRIVACY_AUTH) {
            $license = explode(" ",$project->license);
            $license_logo = 'images/'.mb_strtolower($license[0]).".png";
          } else {
            $license_logo = 'images/cc_srr_primary.png';
          }
          if ($project->privacy == App\Project::PRIVACY_PUBLIC) {
            $lockimage= '<i class="fas fa-lock-open"></i>';
          }
        @endphp

        @if(isset($logo))
          <div class="float-right">
            <img src='{{ url($logo) }}' width='150'>
          </div>
        @endif

        <h3>
          {{ isset($project->title) ? $project->title : $project->name }}
        </h3>
        <!-- short description -->
        @if (isset($project->description))
        <p>
          {{ $project->description }}
        </p>

        @endif
        @if ($project->tags)
        <br>
        <p>
          <strong>
            @lang('messages.tagged_with')
          </strong>:
          {!! $project->tagLinks !!}
        </p>
        <br>
        @endif
        

        <p>
          <a href="http://creativecommons.org/license" target="_blank">
            <img src="{{ asset($license_logo) }}" alt="{{ $project->license }}" width='100px'>
          </a>
          {!! $lockimage !!} @lang('levels.privacy.'.$project->privacy)

        </p>

        @if (isset($project->citation))
          <br>
          <p>
            <strong>@lang('messages.howtocite')</strong>:
            <br>
            {!! $project->citation !!} <a data-toggle="collapse" href="#bibtex" class="btn-sm btn-primary">BibTeX</a>
          </p>
          <div id='bibtex' class='panel-collapse collapse'>
            <pre><code>{{ $project->bibtex }}</code></pre>
          </div>
        @endif

      @if ($project->individuals()->withoutGlobalScopes()->count())
          <p>
          @can('export', $project)
            <form action="{{ url('exportdata')}}" method="POST" class="form-horizontal" >
            <!-- csrf protection -->
              {{ csrf_field() }}
            <!--- field to fill with ids to export --->
              <input type='hidden' name='object_type' value='individual' >
              <input type='hidden' name='project' value='{{ $project->id }}' >
              <input type='hidden' name='filetype' value='csv' >
              <input type='hidden' name='fields' value='all' >
              <button  type='submit' class="btn btn-success">
                <span class="glyphicon glyphicon-download-alt unstyle"></span>
                @lang('messages.data_download')
              </button>
            </form>
          @else
            @if ($project->privacy ==0)
              <a href="{{ url('projects/'.$project->id."/request") }}" class="btn btn-warning">
                <span class="glyphicon glyphicon-download-alt unstyle"></span>
                @lang('messages.data_request')
              </a>
            @else
              <a href="{{ route('login') }}" class="btn btn-warning">
                <span class="glyphicon glyphicon-warning-sign unstyle"></span>
                @lang('messages.download_login')
              </a>
            @endif
        @endcan
      </p>
    @endif




        <!-- START people  BLOCK -->
        <p class="panel-collapse collapse" id='project_details'>
            {{ $project->details }}
        </p>

  <br><br>
  <p>
    @can('view_details',$project)
      @if(isset($project->details))
        <a data-toggle="collapse" href="#project_details" class="btn btn-default">@lang('messages.project_details')</a>
      @endif
    @endcan
    &nbsp;&nbsp;
    <button id='summary_button' type="button" class="btn btn-default">
      <span id='summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
      @lang('messages.summary')
    </button>
    &nbsp;&nbsp;
    <button id='identifications_summary_button' type="button" class="btn btn-default">
      <span id='identifications_summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
      @lang('messages.identifications_summary')
    </button>
    &nbsp;&nbsp;
    <a data-toggle="collapse" href="#project_people" class="btn btn-default">@lang('messages.persons')</a>
  </p>

  <p>
    <a href="{{ url('datasets/'. $project->id. '/project')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
      {{ $project->getCount('all',null,'datasets') }}
      @lang('messages.datasets')
    </a>
    &nbsp;&nbsp;
    <a href="{{ url('individuals/'. $project->id. '/project')  }}" class="btn btn-default">
    <i class="fa fa-btn fa-search"></i>
    {{ $project->getCount('all',null,'individuals') }}
      @lang('messages.individuals')
    </a>
    @if ($project->getCount('all',null,'vouchers'))
      &nbsp;&nbsp;
      <a href="{{ url('vouchers/'. $project->id. '/project')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
        {{ $project->getCount('all',null,'vouchers') }}
        @lang('messages.vouchers')
      </a>
    @endif
    &nbsp;&nbsp;
    <a href="{{ url('taxons/'. $project->id. '/project')  }}" class="btn btn-default">
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
  </p>

  @can ('update', $project)
  <p>
      <a href="{{ url('projects/'. $project->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
        @lang('messages.edit')
      </a>
  </p>
  @endcan

  </div>

<!-- START people  BLOCK -->
<div class="hiddeninfo collapse" id='project_people'>
    <div class="panel-heading">
      <h4>
        @lang('messages.persons')
      </h4>
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
<div class="hiddeninfo" id='project_summary' hidden></div>
<div class="hiddeninfo" id='project_identification_block' hidden></div>
<!-- end -->

<!--- <input type="hidden" id='project_id' value="{{ $project->id }}" > -->



</div>
</div>
</div>
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
