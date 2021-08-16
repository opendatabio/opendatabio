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
        $license_logo = 'images/cc_srr_primary.png';
        if ($dataset->privacy >= App\Models\Dataset::PRIVACY_REGISTERED) {
          $lockimage= '<i class="fas fa-lock-open"></i>';
          $license = explode(" ",$dataset->license);
          $license_logo = 'images/'.mb_strtolower($license[0]).".png";
        }
      @endphp
      <div class="panel-body">
        <h4>
          {{$dataset->title}}
        </h4>
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


        @if (count($dataset->data_type)>0)
        <p>
          @can('export', $dataset)
            <a href="{{ url('datasets/'.$dataset->id."/download") }}" class="btn btn-success">
              <span class="glyphicon glyphicon-download-alt unstyle"></span>
              @lang('messages.data_download')
            </a>
          @else
            @if ($dataset->privacy < App\Models\Dataset::PRIVACY_REGISTERED)
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
  <button id='dataset_summary_button' type="button" type="button" class="btn btn-default">
    <span id='dataset_summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
    @lang('messages.dataset_summary')
  </button>
  &nbsp;&nbsp;
  <button id='identifications_summary_button' type="button" class="btn btn-default">
    <span id='identifications_summary_loading' hidden><i class="fas fa-sync fa-spin"></i></span>
    @lang('messages.taxonomic_summary')
  </button>
  &nbsp;&nbsp;
  <button type="button" class="btn btn-default btntoogle" data='dataset_references' >
   <i class="fas fa-book-open"></i>
   @lang('messages.references')
 </button>
</p>
<br>
<p>
  <a href="{{ url('measurements/'. $dataset->id. '/dataset')  }}" class="btn btn-default" name="submit" value="submit">
        <i class="fa fa-btn fa-search"></i>
        @lang('messages.measurements')
  </a>
  &nbsp;&nbsp;
  <a href="{{ url('individuals/'. $dataset->id. '/dataset')  }}" class="btn btn-default" name="submit" value="submit">
        <i class="fa fa-btn fa-search"></i>
        @lang('messages.individuals')
  </a>

  &nbsp;&nbsp;
  <a href="{{ url('vouchers/'. $dataset->id. '/dataset')  }}" class="btn btn-default" name="submit" value="submit">
        <i class="fa fa-btn fa-search"></i>
        @lang('messages.vouchers')
  </a>
  &nbsp;&nbsp;
  <a href="{{ url('media/'. $dataset->id. '/datasets')  }}" class="btn btn-default" name="submit" value="submit">
        <i class="fa fa-btn fa-search"></i>
        @lang('messages.media_files')
  </a>
</p>
<br>
<p>
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
<!-- START people  BLOCK -->
<div class="hiddeninfo collapse" id='dataset_people'>
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
  @foreach($dataset->people as $role => $list)
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

<!-- START dataset summary BLOCK -->
<div  class='hiddeninfo' id='dataset_summary_block' hidden></div>

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
        url: "{{ route('dataset_identification_summary',$dataset->id) }}",
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

  $('#dataset_summary_button').on('click', function(e){
    if ($('#dataset_summary_block').is(':empty')){
      $('#dataset_summary_loading').show();
      $('.hiddeninfo').hide();
      e.preventDefault();
      $.ajax({
        type: 'POST',
        url: "{{ route('dataset_summary',$dataset->id) }}",
        data: {
          "id" : "{{ $dataset->id }}",
          "_token" : "{{ csrf_token() }}"
        },
        success: function(data) {
            $('#dataset_summary_block').html(data);
            $('#dataset_summary_block').show();
            $('#dataset_summary_loading').hide();
        },
        error: function(data) {
            $('#dataset_summary_block').html("Nothing to display");
            $('#dataset_summary_block').show();
            $('#dataset_summary_loading').hide();
        }
      });
  } else {
    if ($('#dataset_summary_block').is(':visible')) {
      $('#dataset_summary_block').hide();
    } else {
      $('.hiddeninfo').hide();
      $('#dataset_summary_block').show();
    }
  }
  });


});

</script>
@endpush
