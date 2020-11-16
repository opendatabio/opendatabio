@php
  $identification_summary = $project->identification_summary();
  $taxonomic_summary = $project->taxonomic_summary();
@endphp
<div class='panel-heading'>
  <strong>
    @lang('messages.identifications_summary')
  </strong>
</div>
<div class='panel-body'>

  @if(!is_null($taxonomic_summary))
    <strong>@lang('messages.taxonomic_counts')</strong>:
    <p>
    <ul>
    @foreach($taxonomic_summary as $key => $count)
      <li>
        <strong>@lang('messages.'.$key)</strong>
        :
        {{ $count }}
      </li>
     @endforeach
    </ul>
    </p>
  @endif

  @if(!is_null($identification_summary))
    <br>
    <strong>@lang('messages.identification_level')</strong>:
    <p>
    <table class='table table-striped user-table'>
      <thead>
        <th>@lang('messages.taxon_level')</th>
        <th>@lang('messages.unpublished')</th>
        <th>@lang('messages.published')</th>
        <th>@lang('messages.total')</th>
      </thead>
      <tbody>
        @foreach($identification_summary as $detsummary)
        <tr>
            <td class='table-text'>
                @lang('levels.tax.'.$detsummary->level)
            </td>
            <td class='table-text'>
                {{ $detsummary->unpublished }}
            </td>
            <td class='table-text'>
                {{ $detsummary->published }}
            </td>
            <td class='table-text'>
                {{ $detsummary->total }}
            </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    </p>

</div>

@else
<div class="panel-body">
  <strong>@lang('messages.nothing_found')</strong>!
</div>

@endif
