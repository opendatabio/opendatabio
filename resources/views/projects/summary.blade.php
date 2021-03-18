@if(isset($project))
<div class='panel-heading'>
  <h4>
    @lang('messages.project_summary')
  </h4>
</div>
<div class='panel-body'>
  <div class="col-sm-12">
  <table class='table table-striped user-table'>
    <thead>
      <th>@lang('messages.object')</th>
      <th>@lang('messages.count')</th>
      <th>@lang('messages.measurements')</th>
      <th>@lang('messages.taxons')</th>
    </thead>
    <tbody>
      <tr>
          <td class='table-text'>
            @lang('messages.individuals')
          </td>
          <td class='table-text'>
              {{ $project->individualsCount() }}
          </td>
          <td class='table-text'>
              {{ $project->individualsMeasurementsCount() }}
          </td>
          <td class='table-text'>
              {{ $project->taxonsCount() }}
          </td>
      </tr>
      <tr>
          <td class='table-text'>
            @lang('messages.vouchers')
          </td>
          <td class='table-text'>
              {{ $project->vouchersCount() }}
          </td>
          <td class='table-text'>
              {{ $project->vouchersMeasurementsCount() }}
          </td>
          <td class='table-text'>
              
          </td>
      </tr>
    </tbody>
  </table>
  </div>
</div>
@endif
