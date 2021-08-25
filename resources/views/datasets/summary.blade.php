<hr>
<div class="panel-body">
@if(count($data_included)>0)
<table class="table table-striped user-table">
<thead>
  <th>@lang('messages.contains')</th>
  <th>@lang('messages.total')</th>
  <th>@lang('messages.individuals')</th>
  <th>@lang('messages.vouchers')</th>
  <th>@lang('messages.taxons')</th>
  <th>@lang('messages.locations')</th>
</thead>
<tbody>
@foreach($data_included as $key => $data)
<tr>
  <td class="table-text">
      <strong>{{ $key }}</strong>
  </td>
  <td class="table-text">
      {{ $data['Total'] }}
  </td>
  <td class="table-text">
      @if (isset($data['Individuals']))
        {{ $data['Individuals'] }}
      @endif
  </td>
  <td class="table-text">
    @if (isset($data['Vouchers']))
      {{ $data['Vouchers'] }}
    @endif
  </td>
  <td class="table-text">
    @if (isset($data['Taxons']))
      {{ $data['Taxons'] }}
    @endif
  </td>
  <td class="table-text">
    @if (isset($data['Locations']))
      {{ $data['Locations'] }}
    @endif
  </td>
</tr>
@endforeach
</tbody>
</table>
<hr>
@endif

@if(count($plot_included)>0)
<strong>@lang('messages.plot_included')</strong>:
<table class="table table-striped user-table">
<thead>
  <th></th>
  <th>@lang('messages.total')</th>
</thead>
<tbody>
@foreach($plot_included as $key => $value)
<tr>
  <td class="table-text">
      <strong>{{ $key }}</strong>
  </td>
  <td class="table-text">
      {{ $value }}
  </td>
</tr>
@endforeach
</tbody>
</table>
<hr>
@endif

@if (count($trait_summary)>0)
<strong>@lang('messages.measurements_summary')</strong>:
<table class="table table-striped user-table">
<thead>
  <th>@lang('messages.trait')</th>
  <th>@lang('messages.individuals')</th>
  <th>@lang('messages.vouchers')</th>
  <th>@lang('messages.taxons')</th>
  <th>@lang('messages.locations')</th>
  <th>@lang('messages.total')</th>
</thead>
<tbody>
  @php
    $individuals = 0;
    $locations=0;
    $taxons=0;
    $vouchers=0;
    $totals=0;
  @endphp
  @foreach ($trait_summary as $summary)
    @php
      $individuals = $individuals+$summary->individuals;
      $locations = $locations+$summary->locations;
      $taxons = $taxons+$summary->taxons;
      $vouchers = $vouchers+$summary->vouchers;
      $totals = $totals+$summary->total;
    @endphp
    <tr>
        <td class="table-text">
            <a href="{{ url('traits/'.$summary->trait_id) }}">{{ $summary->export_name }}</a>
        </td>
        <td class="table-text">
            @if ($summary->individuals>0)
              <a href="{{ url('measurements/'.$dataset->id.'|'.$summary->trait_id.'|Plant/dataset') }}">{{ $summary->individuals }}</a>
            @else
              {{ $summary->individuals }}
            @endif
        </td>

        <td class="table-text">
            @if ($summary->vouchers>0)
          <a href="{{ url('measurements/'.$dataset->id.'|'.$summary->trait_id.'|Voucher/dataset') }}">{{ $summary->vouchers }}</a>
        @else
          {{ $summary->vouchers }}
        @endif
        </td>

        <td class="table-text">
            @if ($summary->taxons>0)
          <a href="{{ url('measurements/'.$dataset->id.'|'.$summary->trait_id.'|Taxon/dataset') }}">{{ $summary->taxons }}</a>
        @else
          {{ $summary->taxons }}
        @endif
        </td>
        <td class="table-text">
            @if ($summary->locations>0)
          <a href="{{ url('measurements/'.$dataset->id.'|'.$summary->trait_id.'|Location/dataset') }}">{{ $summary->locations }}</a>
        @else
          {{ $summary->locations }}
        @endif
        </td>
        <td class="table-text">
            <a href="{{ url('measurements/'.$dataset->id.'|'.$summary->trait_id.'/dataset') }}">{{ $summary->total }}</a>
        </td>
    </tr>
@endforeach
  <tr>
  <td>
    <strong>
    @lang('messages.total')
    </strong>
  </td>
  <td>
    <strong>
      {{ $individuals }}
    </strong>
  </td>
  <td>
    <strong>
      {{ $vouchers }}
    </strong>
  </td>
  <td>
    <strong>
      {{ $taxons }}
    </strong>
  </td>
  <td>
    <strong>
      {{ $locations }}
    </strong>
  </td>
  <td>
    <strong>
      {{ $totals }}
    </strong>
  </td>
  </tr>
</tbody>
</table>
<hr>
@endif
</div>
