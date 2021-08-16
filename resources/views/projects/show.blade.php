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

        @if(isset($logoUrl))
          <div class="float-right">
            <img src='{{ url($logoUrl) }}' height='150'>
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

        @if ($project->tags()->count())
        <br>
        <p>
          <strong>
            @lang('messages.tagged_with')
          </strong>:
          {!! $project->tagLinks !!}
        </p>
        <br>
        @endif

        <!-- START people  BLOCK -->
        <p class="panel-collapse collapse" id='project_details'>
            {{ $project->details }}
        </p>
<br>
  <p>
    @can('view_details',$project)
      @if(isset($project->details))
        <a data-toggle="collapse" href="#project_details" class="btn btn-default">@lang('messages.project_details')</a>
        &nbsp;&nbsp;
      @endif
    @endcan
    <button id='summary_button' type="button" class="btn btn-default">
      <span id='summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
      @lang('messages.summary')
    </button>
    &nbsp;&nbsp;
    <a data-toggle="collapse" href="#project_people" class="btn btn-default">@lang('messages.persons')</a>
  </p>

  <p>
    <a href="{{ url('datasets/'. $project->id. '/project')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
      {{  $project->datasets()->count() }}
      &nbsp;
      @lang('messages.datasets')
    </a>
    &nbsp;&nbsp;
    <a href="{{ url('individuals/'. $project->id. '/project')  }}" class="btn btn-default">
    <i class="fa fa-btn fa-search"></i>
      @lang('messages.individuals')
    </a>
      &nbsp;&nbsp;
      <a href="{{ url('vouchers/'. $project->id. '/project')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
        @lang('messages.vouchers')
      </a>
    &nbsp;&nbsp;
    <a href="{{ url('taxons/'. $project->id. '/project')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
      @lang('messages.taxons')
    </a>
    &nbsp;&nbsp;
    <a href="{{ url('locations/'. $project->id. '/project')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-search"></i>
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
<hr>
    <div class="panel-heading">
      <h4>
        @lang('messages.persons')
      </h4>
    </div>
    <div class="panel-body">
  <table class="table table-striped user-table">
  <thead>
    <th>@lang('messages.email')</th>
    <th>@lang('messages.name')</th>
    <th>@lang('messages.role')</th>
  </thead>
  <tbody>
  @foreach($project->people as $role => $list)
  @foreach($list as $member)
  <tr>
  <td class="table-text">
      {{ $member[0] }}
  </td>
  <td class="table-text">
      {{ $member[1] }}
  </td>
  <td class="table-text">
      {{ $role }}
  </td>
  </tr>
  @endforeach
  @endforeach
</tbody>
</table>

</div>
</div>


<!-- START summary BLOCK -->
<div class="hiddeninfo" id='project_summary' hidden></div>
<!-- end -->

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
          url: "{{ route('project_summary',$project->id) }}",
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

});

</script>
@endpush
