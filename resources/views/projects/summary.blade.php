@if(isset($project))
<div class='panel-heading'>
  <strong>
    @lang('messages.project_summary')
  </strong>
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
            @lang('messages.plants')
          </td>
          <td class='table-text'>
              {{ $project->plants_public_count() }}
          </td>
          <td class='table-text'>
              {{ $project->plants_public_measurements_count() }}
          </td>
          <td class='table-text'>
              {{ $project->plants_public_taxons_count() }}
          </td>
      </tr>
      <tr>
          <td class='table-text'>
            @lang('messages.vouchers')
          </td>
          <td class='table-text'>
              {{ $project->vouchers_public_count() }}
          </td>
          <td class='table-text'>
              {{ $project->vouchers_public_measurements_count() }}
          </td>
          <td class='table-text'>
              {{ $project->vouchers_public_taxons_count() }}
          </td>
      </tr>
    </tbody>
  </table>
  </div>
</div>
@endif
