@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.dataset')
                    @if(isset($dataset))
                      : <strong>{{$dataset->name}}</strong>
                    @endif
                    @can('update', $dataset)
                    <span class="history" style="float:right">
                      <a href="{{url("datasets/$dataset->id/activity")}}">
                      @lang ('messages.see_history')
                      </a>
                    </span>
                  @endcan
                </div>
		<div class="panel-body">
		    <p><strong>
@lang('messages.name')
: </strong>  {{ $dataset->name }} </p>

<p><strong>
@lang('messages.privacy')
:</strong>
@lang ('levels.privacy.' . $dataset->privacy)
</p>

@if ($dataset->notes)
		    <p><strong>
@lang('messages.notes')
: </strong> {{$dataset->notes}}
</p>
@endif

@if ($dataset->bibreference_id)
		    <p><strong>
@lang('messages.dataset_bibreference')
: </strong>{!! $dataset->reference->rawLink() !!}
</p>
@endif

@if ($dataset->tags)
<p><strong>
@lang('messages.tagged_with')
: </strong> {!! $dataset->tagLinks !!}
</p>
@endif


<div class="col-sm-12">
  <a data-toggle="collapse" href="#dataset_summary" class="btn btn-default">@lang('messages.dataset_summary')</a>
  &nbsp;&nbsp;
  <a data-toggle="collapse" href="#dataset_references" class="btn btn-default">@lang('messages.references')</a>
  &nbsp;&nbsp;
  <a data-toggle="collapse" href="#dataset_people" class="btn btn-default">@lang('messages.persons')</a>
  &nbsp;&nbsp;
  @if ($dataset->measurements()->count())
    <a href="{{ url('datasets/'. $dataset->id. '/measurements')  }}" class="btn btn-default" name="submit" value="submit">
          <i class="fa fa-btn fa-search"></i>
          {{ $dataset->measurements()->count() }}
          @lang('messages.measurements')
    </a>
  @endif
</div>

<div class="col-sm-12">
<br><br>
@can ('update', $dataset)
  <div class="col-sm-3 float-left">
    <a href="{{ url('datasets/'. $dataset->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
      <i class="fa fa-btn fa-plus"></i>
      @lang('messages.edit')
    </a>
  </div>
@endcan
  <div class="col-sm-3 float-right">
@can('export', $dataset)
  <a href="{{ url('datasets/'.$dataset->id."/download") }}" class="btn btn-success">
    <span class="glyphicon glyphicon-download-alt unstyle"></span>
    @lang('messages.tooltip_download_dataset')
  </a>
@else
  <a href="{{ url('datasets/'.$dataset->id."/request") }}" class="btn btn-warning">
    <span class="glyphicon glyphicon-download-alt unstyle"></span>
    @lang('messages.tooltip_request_dataset')
  </a>
@endcan
  </div>
</div>










</div>
</div>

      <!-- START dataset summary BLOCK -->
        <div class="panel panel-default panel-collapse collapse" id='dataset_people'>
          <div class="panel-heading">
            <strong>
              @lang('messages.persons')
            </strong>
          </div>
          <div class="panel-body">
            <p><strong>
            @lang('messages.admins')
            :</strong>
            <ul>
            @foreach ($dataset->users()->wherePivot('access_level', '=', App\Project::ADMIN)->get() as $admin)
            @if(isset($admin->person))
              <li>{{ $admin->person->full_name." -  ".$admin->email }} </li>
            @else
              <li>{{ $admin->email }} </li>
            @endif
            @endforeach
            </ul>
            </p>

            <p><strong>
            @lang('messages.collaborators')
            :</strong>
            <ul>
            @foreach ($dataset->users()->wherePivot('access_level', '=', App\Project::COLLABORATOR)->get() as $admin)
              @if(isset($admin->person))
                <li>{{ $admin->person->full_name." -  ".$admin->email }} </li>
              @else
                <li>{{ $admin->email }} </li>
              @endif
            @endforeach
            </ul>
            </p>

            <p><strong>
            @lang('messages.viewers')
            :</strong>
            <ul>
            @foreach ($dataset->users()->wherePivot('access_level', '=', App\Project::VIEWER)->get() as $admin)
              @if(isset($admin->person))
                <li>{{ $admin->person->full_name." -  ".$admin->email }} </li>
              @else
                <li>{{ $admin->email }} </li>
              @endif
            @endforeach
            </ul>
            </p>
          </div>
        </div>

        <!-- START dataset summary BLOCK -->
        <div class="panel panel-default panel-collapse collapse" id='dataset_summary'>
          <div class="panel-heading">
            <strong>
            @lang('messages.dataset_summary_hint')
            </strong>
          </div>
          <div class="panel-body">
          @if (isset($trait_summary))
        <table class="table table-striped user-table">
          <thead>
            <th>@lang('messages.trait')</th>
            <th>@lang('messages.plants')</th>
            <th>@lang('messages.vouchers')</th>
            <th>@lang('messages.taxons')</th>
            <th>@lang('messages.locations')</th>
            <th>@lang('messages.total')</th>
          </thead>
          <tbody>
            @foreach ($trait_summary as $summary)
              <tr>
                  <td class="table-text">
                      <a href="{{ url('traits/'.$summary->trait_id) }}">{{ $summary->export_name }}</a>
                  </td>
                  <td class="table-text">
                      @if ($summary->plants>0)
                        <a href="{{ url('datasets/'.$dataset->id.'|'.$summary->trait_id.'|Plant/measurements') }}">{{ $summary->plants }}</a>
                      @else
                        {{ $summary->plants }}
                      @endif
                  </td>

                  <td class="table-text">
                      @if ($summary->vouchers>0)
                    <a href="{{ url('datasets/'.$dataset->id.'|'.$summary->trait_id.'|Voucher/measurements') }}">{{ $summary->vouchers }}</a>
                  @else
                    {{ $summary->vouchers }}
                  @endif
                  </td>

                  <td class="table-text">
                      @if ($summary->taxons>0)
                    <a href="{{ url('datasets/'.$dataset->id.'|'.$summary->trait_id.'|Taxon/measurements') }}">{{ $summary->taxons }}</a>
                  @else
                    {{ $summary->taxons }}
                  @endif
                  </td>
                  <td class="table-text">
                      @if ($summary->locations>0)
                    <a href="{{ url('datasets/'.$dataset->id.'|'.$summary->trait_id.'|Location/measurements') }}">{{ $summary->locations }}</a>
                  @else
                    {{ $summary->locations }}
                  @endif
                  </td>
                  <td class="table-text">
                      <a href="{{ url('datasets/'.$dataset->id.'|'.$summary->trait_id.'/measurements') }}">{{ $summary->total }}</a>
                  </td>
              </tr>
          @endforeach
      </tbody>
    </table>
    @endif
    </div>
  </div>
    <!-- END dataset summary BLOCK -->

    <!-- start REFERENCE BLOCK -->
    <div class="panel panel-default panel-collapse collapse" id='dataset_references'>
      <div class="panel-heading">
        <strong>
        @lang('messages.dataset_bibreference')
        </strong>
      </div>
      <div class="panel-body">
      @if ($dataset->references)
      <table class="table table-striped">
        <thead>
        <tr>
          <th></th>
          <th>@lang('messages.bibtex_key')</th>
          <th>@lang('messages.author')</th>
          <th>@lang('messages.year')</th>
          <th>@lang('messages.title')</th>
       <tr>
       </thead>
       <tbody>
      @foreach($dataset->references as $reference)
        <tr>
          <td class="table-text">
            @if( 0 !== $reference->mandatory)
              <span class="glyphicon glyphicon-asterisk text-danger" data-toggle="tooltip" style="cursor:pointer" title="@lang('messages.dataset_bibreferences_mandatory')"></span>
            @endif
          </td>
          <td class="table-text">
            <a href='{{ url('references/'.$reference->bib_reference_id)}}'>
              {{ $reference->bibkey }}
            </a>
          </td>
          <td class="table-text">{{ $reference->first_author }}</td>
          <td class="table-text">{{ $reference->year }}</td>
          <td class="table-text">{{ $reference->title }}</td>
        </tr>
      @endforeach
      </tbody>
      </table>
      @if($dataset->references->where('mandatory',1)->count())
            <span class="glyphicon glyphicon-asterisk text-danger"></span>
            @lang('messages.dataset_bibreferences_mandatory')
      @endif
      @endif
    </div>
  </div>
  <!-- END REFERENCE BLOCK -->




</div>

@endsection
