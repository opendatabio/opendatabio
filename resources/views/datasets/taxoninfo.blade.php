@php
  $identification_summary = $dataset->identification_summary();
  $taxonomic_summary = $dataset->taxonomic_summary();
@endphp
<div class='panel-heading'>
  <h4>
    @lang('messages.identifications_summary')
  </h4>
</div>
@if(!is_null($taxonomic_summary) or !is_null($identification_summary))
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
  @endif
  </div>

@else
<div class="panel-body">
  <strong>@lang('messages.dataset_hasNoTaxons')</strong>!
  <br>
  <strong>@lang('messages.measured_types')</strong>
  @php
    $objs = $dataset->measured_classes_counts()->toArray();
  @endphp
    <ul>
    @foreach($objs as $object)
      <li>
        <strong>{{ $object->measured_type }}</strong>
        :
        {{ $object->count }}
      </li>
     @endforeach
    </ul>
</div>

@endif
