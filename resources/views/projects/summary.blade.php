@if(isset($project))
<hr>
<div class='panel-heading'>
  <h4>
    @lang('messages.project_summary')
  </h4>
</div>
<div class='panel-body'>
@php
  $details = $project->describe_project();
  $taxonomic_summary = $project->taxonomic_summary();
  $identification_summary = $project->identification_summary();
@endphp
@if(count($details)>0)
  <table class='table table-striped user-table'>
    <thead>
      <th>@lang('messages.object')</th>
      <th>@lang('messages.count')</th>
    </thead>
    <tbody>
      @foreach($details as $key => $count)
      <tr>
          <td class='table-text'>
            @lang('messages.'.$key)
          </td>
          <td class='table-text'>
              {{ $count }}
          </td>
      </tr>
      @endforeach
    </tbody>
  </table>
@endif

{!! View::make('datasets.taxoninfo', ['taxonomic_summary' => $taxonomic_summary, 'identification_summary' => $identification_summary ]) !!}


</div>
@endif
