@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.taxon')
<div style="float:right;">
@if ($taxon->mobot)
<a href="http://tropicos.org/Name/{{$taxon->mobot}}" target="_blank"><img src="{{asset('images/TropicosLogo.gif')}}" alt="Tropicos"></a>
@endif
@if ($taxon->ipni)
<a href="http://www.ipni.org/ipni/idPlantNameSearch.do?id={{$taxon->ipni}}" target="_blank"><img src="{{asset('images/IpniLogo.png')}}" alt="IPNI" width="33px"></a>
@endif
@if ($taxon->mycobank)
<a href="http://www.mycobank.org/Biolomics.aspx?Table=Mycobank&Rec={{$taxon->mycobank}}&Fields=All" target="_blank"><img src="{{asset('images/MBLogo.png')}}" alt="Mycobank" width="33px"></a>
@endif
@if ($taxon->zoobank)
<a href=="http://zoobank.org/NomenclaturalActs/{{$taxon->zoobank}}" target="_blank"><img src="{{asset('images/zoobank.png')}}" alt="ZOOBANK" width="33px"></a>
@endif
&nbsp;
<span class="history" style="float:right">
<a href="{{url("taxons/$taxon->id/activity")}}">
@lang ('messages.see_history')
</a>
</span>

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
@if ($taxon->getCount("all",null,'plants'))
<a href="{{ url('plants/'. $taxon->id. '/taxon')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $taxon->getCount("all",null,'plants') }}
@lang('messages.plants')
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
@can ('create', App\Measurement::class)
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
@can ('create', App\Picture::class)
<a href="{{ url('taxons/'. $taxon->id. '/pictures/create')  }}" class="btn btn-success">
        <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_picture')
    </a>
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



@if ( $taxon->getCount("all",null,'pictures'))

{!! View::make('pictures.index', ['pictures' => $taxon->allpictures()]) !!}

@endif



<!-- Other details (specialist, herbarium, collects, etc?) -->
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


</div>

</div>
@endsection
@push ('scripts')
{!! $dataTable->scripts() !!}
@endpush
