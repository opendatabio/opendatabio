@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.taxon')
<div style="float:right;">
@if ($taxon->mobot)
<a href="http://tropicos.org/Name/{{$taxon->mobot}}" data-toggle="tooltip" rel="tooltip" data-placement="right" title="Tropicos.org"  target="_blank"><img src="{{asset('images/TropicosLogo.gif')}}" alt="Tropicos"></a>
@endif
@if ($taxon->ipni)
&nbsp;
<a href="http://www.ipni.org/ipni/idPlantNameSearch.do?id={{$taxon->ipni}}" data-toggle="tooltip" rel="tooltip" data-placement="right" title="IPNI.org"   target="_blank"><img src="{{asset('images/IpniLogo.png')}}" alt="IPNI" width="33px"></a>
@endif
@if ($taxon->mycobank)
&nbsp;
<a href="http://www.mycobank.org/Biolomics.aspx?Table=Mycobank&Rec={{$taxon->mycobank}}&Fields=All" data-toggle="tooltip" rel="tooltip" data-placement="right" title="MycoBank.org"  target="_blank"><img src="{{asset('images/MBLogo.png')}}" alt="Mycobank" width="33px"></a>
@endif
@if ($taxon->zoobank)
&nbsp;
<a href=="http://zoobank.org/NomenclaturalActs/{{$taxon->zoobank}}" data-toggle="tooltip" rel="tooltip" data-placement="right" title="ZooBank.org"  target="_blank"><img src="{{asset('images/zoobank.png')}}" alt="ZOOBANK" width="33px"></a>
@endif
@if ($taxon->gbif)
&nbsp;
<a href="https://www.gbif.org/species/{{$taxon->gbif}}" data-toggle="tooltip" rel="tooltip" data-placement="right" title="GBIF.org" target="_blank"><img src="{{asset('images/GBIF-2015-mark.png')}}" height="33px"></a>
@endif
&nbsp;
<a href="http://servicos.jbrj.gov.br/flora/search/{{ urlencode($taxon->name) }}" data-toggle="tooltip" rel="tooltip" data-placement="right" title="Flora-Brazil" target="_blank"><img src="{{asset('images/logofb.png')}}" height="33px"></a>

&nbsp;&nbsp;&nbsp;
<a class="history" href="{{url("taxons/$taxon->id/activity")}}">
@lang ('messages.see_history')</a>


</div>

<div class="panel-body">
  <div class="col-sm-12">

                    <!-- Display Validation Errors -->
		    <p><strong>
@lang('messages.name')
: </strong> <em> {{ $taxon->fullname }} </em></p>

<p><strong>
@lang('messages.author')
:</strong>
@if ($author)
{!! $author->rawLink() !!}
@else
{{ $taxon->author }}
@endif
</p>

@if ( $bibref or $taxon->bibreference )
<p><strong>
@lang('messages.bibreference')
:</strong>
@if ( $taxon->bibreference)
{{ $taxon->bibreference }}
@endif
@if ($bibref)
{!! $bibref->rawLink() !!}
@endif
</p>
@endif

		    <p><strong>
@lang('messages.level')
: </strong>
@lang ('levels.tax.' . $taxon->level)
</p>


<p><strong>
@lang('messages.valid_status')
: </strong>
@if ($taxon->author_id)
@lang ('messages.unpublished')
@elseif ($taxon->valid)
@lang ('messages.isvalid')
@else
        @lang ('messages.notvalid')
@endif
</p>

@if ($taxon->persons->count())
<p><strong>
@lang('messages.specialists')
: </strong>
<ul>
@foreach ($taxon->persons as $person)
<li>{!! $person->rawLink() !!}</li>
@endforeach
</ul>
</p>
@endif

@if ($taxon->notes)
		    <p><strong>
@lang('messages.notes')
: </strong> {{$taxon->notes}}
</p>
@endif

</div>


<div class="col-sm-12">
<br>
@if ($taxon->references->count())
  <a data-toggle="collapse" href="#taxon_references" class="btn btn-default">@lang('messages.references')</a>
  &nbsp;&nbsp;
@endif

@if ($taxon->getCount("all",null,'measurements'))
<a href="{{ url('measurements/'. $taxon->id. '/taxon')  }}" class="btn btn-default">
<i class="fa fa-btn fa-search"></i>
{{ $taxon->getCount("all",null,'measurements') }}
@lang('messages.measurements')
</a>
&nbsp;&nbsp;
@endif
@if ($taxon->getCount("all",null,'individuals'))
<a href="{{ url('individuals/'. $taxon->id. '/taxon')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $taxon->getCount("all",null,'individuals') }}
@lang('messages.individuals')
    </a>
&nbsp;&nbsp;
@endif
@if ($taxon->getCount("all",null,'vouchers'))
<a href="{{ url('vouchers/'. $taxon->id. '/taxon')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $taxon->getCount("all",null,'vouchers') }}
@lang('messages.vouchers')
    </a>
&nbsp;&nbsp;
@endif
</div>


<div class="col-sm-12">
<br>
@can ('create', App\Models\Measurement::class)
<a href="{{ url('taxons/'. $taxon->id. '/measurements/create')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_measurements')
    </a>
&nbsp;&nbsp;
@endcan
@can ('update', $taxon)
<a href="{{ url('taxons/'. $taxon->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
@lang('messages.edit')
</a>
&nbsp;&nbsp;
@endcan

<!-- this will show only if no media as media are shown below -->
@can ('create', App\Models\Media::class)
  @if (null == $media)
<a href="{{ url('taxons/'. $taxon->id. '/media-create')  }}" class="btn btn-success">
      <i class="fa fa-btn fa-plus"></i>
      <i class="fas fa-photo-video"></i>
      <i class="fas fa-headphones-alt"></i>
      @lang('messages.create_media')
    </a>
  @endif
@endcan

</div>
                </div>
            </div>
        </div>

<!-- start REFERENCE BLOCK -->
<div class="panel panel-default panel-collapse collapse" id='taxon_references'>
  <div class="panel-heading">
    <strong>
    @lang('messages.references')
    </strong>
  </div>
  <div class="panel-body">
  @if ($taxon->references)
  <table class="table table-striped">
    <thead>
    <tr>
      <th>@lang('messages.bibtex_key')</th>
      <th>@lang('messages.author')</th>
      <th>@lang('messages.year')</th>
      <th>@lang('messages.title')</th>
   <tr>
   </thead>
   <tbody>
  @foreach($taxon->references as $reference)
    <tr>
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
  @endif
</div>
</div>

<!-- end REFERENCES BLOCK -->
<!-- Other details (specialist, biocollection, collects, etc?) -->
@if ($taxon->senior or $taxon->juniors->count())
<div class="panel panel-default">
  <div class="panel-heading">
    @lang('messages.taxon_sinonimia')
  </div>
  <div class="panel-body">
    @if ($taxon->senior)
      <p>
      @lang ('messages.accepted_name'):
      {!! $taxon->senior->rawLink() !!}
      </p>
    @endif
    @if ($taxon->juniors->count())
      <p>
      @lang ('messages.juniors'):
      <ul>
      @foreach ($taxon->juniors as $junior)
        <li>{!! $junior->rawLink() !!}</li>
      @endforeach
      </ul>
    @endif
</div>
</div>
@endif

<!-- RELATED TAXA BLOCK-->
<div class="panel panel-default">
  <div class="panel-heading">
    <a data-toggle="collapse" href="#related_taxa" class="btn btn-default">
      @lang('messages.taxon_ancestors_and_children')
    </a>
  </div>
  <div class="panel-collapse collapse"  id='related_taxa'>
    <div class="panel-body">
      <strong>@lang('messages.taxon_ancestors')</strong>:
      <br>
      @if ($taxon->getAncestors()->count())
        @foreach ($taxon->getAncestors() as $ancestor)
          {!! $ancestor->rawLink() !!} &gt;
        @endforeach
        @endif
        <strong>{{ $taxon->qualifiedFullname }}</strong>
        <br>
        @if ($taxon->getDescendants()->count())
          <hr>
          <strong>@lang('messages.taxon_children')</strong>:
          <br><br>
        {!! $dataTable->table() !!}
        @endif
    </div>
  </div>
</div>

@if (null != $media)
  {!! View::make('media.index-model', ['model' => $taxon, 'media' => $media ]) !!}
@endif

</div>

</div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}


<script>
    const table = $('#dataTableBuilder');
    table.on('preXhr.dt',function(e,settings,data) {
      data.level = $("#taxon_level option").filter(':selected').val();
      data.project = $("input[name='project']").val();
      data.location = $("input[name='location_root']").val();
      data.taxon = $("input[name='taxon_root']").val();
      //console.log(data.level,data.project,data.location,data.taxon);
    });
    $('#taxon_level').on('change',function() {
       table.DataTable().ajax.reload();
       return false;
    });
</script>


@endpush
