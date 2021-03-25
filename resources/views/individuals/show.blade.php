@extends('layouts.app')
@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">

      <div class="panel-heading">
        @lang('messages.individual'): <strong>{{ $individual->fullname}}</strong>
        <span class="history" style="float:right">
          <a href="{{url("individuals/$individual->id/activity")}}">
            @lang ('messages.see_history')
          </a>
        </span>
      </div>
      <div class="panel-body">

        <p><strong>@lang('messages.identification'): </strong>
          @if (is_null($identification))
              @lang ('messages.unidentified')
              </p>
          @else
              {!! $identification->rawLink(); !!}
            </p>
            <p><strong>@lang('messages.identified_by'):</strong>
            @if ($identification->person)
              {!! $identification->person->rawLink() !!} ({{ $identification->formatDate }})
            @else
              @lang('messages.not_registered')
            @endif
            </p>
          @if ($identification->biocollection_id)
            <p><strong>@lang('messages.identification_based_on'):</strong>
              @lang('messages.voucher') {{ $identification->biocollection_reference }} &#64;
              {!! $identification->biocollection->rawLink() !!}
            </p>
          @endif
          @if ($identification->notes)
            <p><strong>@lang('messages.identification_notes'):</strong>
              {{ $identification->notes }}
            </p>
          @endif
        @endif

<hr>

        <p>
          <strong>
            @lang('messages.individual_tag')
          </strong>
          <i class="fa fa-hashtag" aria-hidden="true"></i>
          &nbsp;
          <span style='font-size: 1.5em;'>
            {{ $individual->tag }}
          </span>
        </p>

        <p><strong>@lang('messages.collectors'):</strong>
        @if ($collectors->count())
            @php
              $first = 1;
            @endphp
            @foreach ($collectors as $collector)
              @if ($first==1)
                  {!! $collector->person->rawLink() !!}
              @else
                  | {!! $collector->person->abbreviation !!}
              @endif
              @php
                $first =0;
              @endphp
            @endforeach
        @else
            @lang('messages.not_registered')
        @endif
        </p>

        <p><strong>@lang('messages.date'):</strong>
          {{$individual->formatDate}}
        </p>

        <hr>
        <!--- LOCATION -->
        <?php // TODO: NEED TO ADD MULTIPLE LOCATIONS VIEW HERE ?>
          <p>
          <strong>@lang('messages.location'): </strong>
          @if($individual->locations->count())
            &nbsp;&nbsp;{!! $individual->locations->last()->rawLink() !!} <br> {!! $individual->LocationDisplay(); !!}
            <br>{!! $individual->locations->last()->precision !!} &nbsp;&nbsp;<a data-toggle="collapse" href='#hintp' class="btn btn-default">?</a>
            @if ($individual->locations->count()>1)
              <br><a data-toggle="collapse" href='#locationdatatable' class="btn btn-default">@lang('messages.all')</a>
            @endif
          @else
            @lang('messages.unknown_location')
          @endif
        </p>
        <div id='hintp' class='panel-collapse collapse'>
            @lang('messages.location_precision_hint')
        </div>
        @if($individual->locations->count()>1)
        <div id='locationdatatable' class='panel-collapse collapse' >{!! $dataTable->table([],true) !!}</div>
        @endif
      <hr>


      <p><strong>@lang('messages.project'):</strong>
        {!! $individual->project->rawLink() !!}
      </p>

      @if ($individual->notes)
        <p><strong>@lang('messages.notes'):</strong>
          {{$individual->notes}}
        </p>
      @endif

      <hr>

      @if ($individual->measurements()->withoutGlobalScopes()->count())
        <div class="col-sm-4">
          <a href="{{ url('measurements/'. $individual->id. '/individual')  }}" class="btn btn-default">
            <i class="fa fa-btn fa-search"></i>
            {{ $individual->measurements()->withoutGlobalScopes()->count() }}
            @lang('messages.measurements')
          </a>
        </div>
      @else
        @can ('create', App\Measurement::class)
          <div class="col-sm-4">
            <a href="{{ url('individuals/'. $individual->id. '/measurements/create')  }}" class="btn btn-default">
              <i class="fa fa-btn fa-search"></i>
              @lang('messages.create_measurements')
            </a>
          </div>
        @endcan
      @endif


      @if ($individual->vouchers()->count())
        <div class="col-sm-4">
          <a href="{{ url('individuals/'. $individual->id. '/vouchers')  }}" class="btn btn-default">
            <i class="fa fa-btn fa-search"></i>
            {{ $individual->vouchers()->count() }}
            @lang('messages.vouchers')
          </a>
        </div>
      @else
        @can ('create', App\Voucher::class)
          <div class="col-sm-4">
            <a href="{{url ('individuals/' . $individual->id . '/vouchers/create')}}" class="btn btn-default">
              <i class="fa fa-btn fa-plus"></i>
              @lang('messages.create_voucher')
            </a>
          </div>
        @endcan
      @endif

      @can ('create', App\Picture::class)
        <div class="col-sm-3">
          <a href="{{ url('individuals/'. $individual->id. '/pictures/create')  }}" class="btn btn-default">
            <i class="fa fa-btn fa-plus"></i>
            @lang('messages.create_picture')
          </a>
        </div>
      @endcan


      <br><br>
      @can ('update', $individual)
			    <div class="col-sm-3">
            <a href="{{ url('individuals/'. $individual->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
              @lang('messages.edit')
            </a>
          </div>
      @endcan



      </div>
    </div>
    @if ($individual->pictures->count())
      {!! View::make('pictures.index', ['pictures' => $individual->pictures]) !!}
    @endif
</div>
</div>
@endsection

@push ('scripts')

{!! $dataTable->scripts() !!}

@endpush
