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
      @php
        $lockimage= '<i class="fas fa-lock"></i>';
        if ($dataset->privacy != App\Models\Dataset::PRIVACY_AUTH) {
          $license = explode(" ",$dataset->license);
          $license_logo = 'images/'.mb_strtolower($license[0]).".png";
        } else {
          $license_logo = 'images/cc_srr_primary.png';
        }
        if ($dataset->privacy == App\Models\Dataset::PRIVACY_PUBLIC) {
          $lockimage= '<i class="fas fa-lock-open"></i>';
        }
      @endphp
      <div class="panel-body">
        <h3>
          {{$dataset->title}}
        </h3>
        @if (isset($dataset->description))
        <p>
          {{ $dataset->description }}
        </p>
        @endif
        @if ($dataset->tags->count())
        <br>
        <p>
          <strong>
            @lang('messages.tagged_with')
          </strong>:
          {!! $dataset->tagLinks !!}
        </p>
        @endif
        <br>
        <span style='float:right;'>
          <h2>
            {{ $dataset->downloads }}
          </h2>
          <small>Downloads</small>
        </span>
        <p>
          <a href="http://creativecommons.org/license" target="_blank">
            <img src="{{ asset($license_logo) }}" alt="{{ $dataset->license }}" width='100px'>
          </a>
          {!! $lockimage !!} @lang('levels.privacy.'.$dataset->privacy)

        </p>
        @if ($dataset->measurements()->withoutGlobalScopes()->count())
        <p>
          @can('export', $dataset)
            <a href="{{ url('datasets/'.$dataset->id."/download") }}" class="btn btn-success">
              <span class="glyphicon glyphicon-download-alt unstyle"></span>
              @lang('messages.data_download')
            </a>
          @else
            @if ($dataset->privacy ==0)
              <a href="{{ url('datasets/'.$dataset->id."/request") }}" class="btn btn-warning">
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
        @if (isset($dataset->citation))
          <br>
          <p>
            <strong>@lang('messages.howtocite')</strong>:
            <br>
            {!! $dataset->citation !!}  <a data-toggle="collapse" href="#bibtex" class="btn-sm btn-primary">BibTeX</a>
          </p>
          <div id='bibtex' class='panel-collapse collapse'>
            <pre><code>{{ $dataset->bibtex }}</code></pre>
          </div>
        @endif
        @if ($dataset->policy)
          <br>
          <p>
            <strong>
              @lang('messages.data_policy')
            </strong>:
            <br>
            {{$dataset->policy}}
          </p>
        @endif
        <br>

<p>
  <button type="button" class="btn btn-default btntoogle" data='dataset_summary' >@lang('messages.dataset_summary')</button>
  &nbsp;&nbsp;
  <button id='identifications_summary_button' type="button" class="btn btn-default">
    <span id='identifications_summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
    @lang('messages.identifications_summary')
  </button>

  @if ($dataset->measurements()->withoutGlobalScopes()->count())
    <a href="{{ url('measurements/'. $dataset->id. '/dataset')  }}" class="btn btn-default" name="submit" value="submit">
          <i class="fa fa-btn fa-search"></i>
          {{ $dataset->measurements()->withoutGlobalScopes()->count() }}
          @lang('messages.measurements')
    </a>
    &nbsp;&nbsp;
  @endif
</p>
<br>
<p>
  <button type="button" class="btn btn-default btntoogle" data='dataset_references' >
    <i class="fas fa-book-open"></i>
    @lang('messages.references')
  </button>


    &nbsp;&nbsp;
    <a href="{{ url('individuals/'. $dataset->id. '/datasets')  }}" class="btn btn-default" name="submit" value="submit">
          <i class="fa fa-btn fa-search"></i>
          @lang('messages.individuals')
    </a>
  
    &nbsp;&nbsp;
    <a href="{{ url('vouchers/'. $dataset->id. '/dataset')  }}" class="btn btn-default" name="submit" value="submit">
          <i class="fa fa-btn fa-search"></i>
          @lang('messages.vouchers')
    </a>

    &nbsp;&nbsp;
    <a href="{{ url('locations/'. $dataset->id. '/dataset')  }}" class="btn btn-default" name="submit" value="submit">
          <i class="fa fa-btn fa-search"></i>
          @lang('messages.locations')
    </a>
    &nbsp;&nbsp;
    <a href="{{ url('taxons/'. $dataset->id. '/dataset')  }}" class="btn btn-default" name="submit" value="submit">
          <i class="fa fa-btn fa-search"></i>
          @lang('messages.taxons')
    </a>
  &nbsp;&nbsp;
  <button type="button" class="btn btn-default btntoogle" data='dataset_people' >@lang('messages.persons')</button>
</p>
</div>

<!-- START dataset people block -->
<div class='hiddeninfo' id='dataset_people' hidden>
  <hr>
  <div class="panel-heading">
    <h4>
      @lang('messages.persons')
    </h4>
  </div>
  <div class="panel-body">
    <p><strong>
    @lang('messages.admins')
    :</strong>
    <ul>
    @foreach ($dataset->users()->wherePivot('access_level', '=', App\Models\Project::ADMIN)->get() as $admin)
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
    @foreach ($dataset->users()->wherePivot('access_level', '=', App\Models\Project::COLLABORATOR)->get() as $admin)
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
    @foreach ($dataset->users()->wherePivot('access_level', '=', App\Models\Project::VIEWER)->get() as $admin)
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
    <div  class='hiddeninfo' id='dataset_summary' hidden>
      <hr>
      <div class="panel-heading">
        <h4>
        @lang('messages.dataset_summary_hint')
        </h4>
      </div>
      <div class="panel-body">
      @if (isset($trait_summary))
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
    @endif
    </div>
    </div>

<!-- IDENTIFICATIONS SUMMARY PANEL DO NOT CHANGE THIS LINE  MAY BREAK jquery below-->
<div class="panel-body hiddeninfo" id='identifications_summary_block' hidden></div>

    <!-- END dataset summary BLOCK -->

<!-- start REFERENCE BLOCK -->
<div class='hiddeninfo' id='dataset_references' hidden>
    <hr>
    <div class="panel-heading">
      <h4>
      @lang('messages.dataset_bibreference')
      </h4>
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

  @can ('update', $dataset)
  <div class="panel-body">
      <a href="{{ url('datasets/'. $dataset->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
        <i class="fa fa-btn fa-plus"></i>
        @lang('messages.edit')
      </a>
  </div>
  @endcan

</div>


</div>
@endsection


@push ('scripts')

<script>

$(document).ready(function() {


/* generic function to close and focus on page elements */
$('.btntoogle').on('click',function() {
    var selected = $(this);
    var element = selected.attr('data');
    var container = $('.container');
    if ($('#'+element).is(":hidden")) {
      $('.hiddeninfo').hide();
      $('#'+element).show();
      var scrollTo = $('#'+element);
      var position = scrollTo.offset().top;
      window.scrollTo(null,position);
    } else {
      $('#'+element).hide();
      window.scrollTo(null,container.offset().top);
    }
});


 /* summarize identifications */
  $('#identifications_summary_button').on('click', function(e){
    if ($('#identifications_summary_block').is(':empty')){
      $('#identifications_summary_loading').show();
      $('.hiddeninfo').hide();
      e.preventDefault();
      $.ajax({
        type: 'POST',
        url: "{{ route('datasetTaxonInfo',$dataset->id) }}",
        data: {
          "id" : "{{ $dataset->id }}",
          "_token" : "{{ csrf_token() }}"
        },
        success: function(data) {
            $('#identifications_summary_block').html(data);
            $('#identifications_summary_block').show();
            $('#identifications_summary_loading').hide();
        },
        error: function(data) {
            $('#identifications_summary_block').html("NOT FOUND");
            $('#identifications_summary_block').show();
            $('#identifications_summary_loading').hide();
        }
      });
  } else {
    if ($('#identifications_summary_block').is(':visible')) {
      $('#identifications_summary_block').hide();
    } else {
      $('.hiddeninfo').hide();
      $('#identifications_summary_block').show();
    }
  }
  });




});

</script>
@endpush
