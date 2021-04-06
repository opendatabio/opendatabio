@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-sm-offset-2 col-sm-8">
    <div class="panel panel-default">
      <div class="panel-heading">
          @lang('messages.voucher')&nbsp;&nbsp;
          <strong>
            {{ $voucher->fullname }}
          </strong>
          <span class="history" style="float:right">
          <a href="{{url("vouchers/$voucher->id/activity")}}">
          @lang ('messages.see_history')
          </a>
          </span>
      </div>

      <div class="panel-body">
        <p>
          <strong>@lang ('messages.main_collector')</strong>:
          {!! $voucher->main_collector !!}
        </p>
        <p>
          <strong>@lang('messages.voucher_number')</strong>:
          {{ isset($voucher->number) ? $voucher->number : $voucher->individual->tag }}
        </p>
        <p>
          <strong>@lang('messages.collection_date')</strong>:
          {{ $voucher->collection_date }}
        </p>
        @if ($voucher->main_collector != $voucher->all_collectors)
          <p>
            <strong>@lang('messages.collectors')</strong>:
            {{ $voucher->all_collectors }}
          </p>
        @endif
        <hr>
        <p>
          <strong>@lang('messages.biocollection')</strong>:
          {!! $voucher->biocollection->rawLink() !!} -
          {{ $voucher->biocollection->name }}
        </p>
        @if ($voucher->biocollection_number)
          <p>
            <strong>@lang('messages.biocollection_number')</strong>:
            {{ $voucher->biocollection_number }}
          </p>
        @endif
        <p>
          <strong>@lang('messages.voucher_isnomenclatural_type')</strong>?
          {{ $voucher->is_type }}
        </p>

        <hr>
           <p>
            <strong>@lang('messages.individual')</strong>:
            {!! $voucher->individual->rawLink() !!}
          </p>

          <p>
          <strong>@lang('messages.identification')</strong>:
           @if (is_null($identification))
             @lang ('messages.unidentified')
            </p>
           @else
             {!! $identification->rawLink() !!}
        </p>
        <p>
          <strong>
             @lang('messages.identified_by')
             :</strong>
             {!! $identification->person ? $identification->person->rawLink() : Lang::get('messages.not_registered') !!}
             ({{ $identification->formatDate }})
           </p>
           @if ($identification->biocollection_id)
             <p><strong>
               @lang('messages.identification_based_on')
               :</strong>
               @lang('messages.voucher_deposited_at') {!! $identification->biocollection->rawLink() !!}  @lang('messages.under_biocollections_id') {{ $identification->biocollection_reference }}
             </p>
           @endif
           @if ($identification->notes)
             <p><strong>
               @lang('messages.identification_notes')
               :</strong>
               {{ $identification->notes }}
             </a>
            </p>
            @endif
          @endif


          <!-- END identification -->


            <p>
             <strong>@lang('messages.location')</strong>:
              <br>
                {!! $voucher->location_display !!}
              <br><br>
              {{ $voucher->locationWithGeom->centroid_raw }}&nbsp;&nbsp;
              <a data-toggle="collapse" href='#hintp' class="btn btn-default">{!! $voucher->individual->location_first->first()->precision !!}</a>
              <div id='hintp' class='panel-collapse collapse'>
                @lang('messages.location_precision_hint')
              </div>
            </p>

            <hr>
            @if ($voucher->notes)
              <p>
                <strong>@lang('messages.notes')</strong>:
                {{$voucher->notes}}
              </p>
            <hr>
            @endif


        <p>
        <strong>@lang('messages.project')</strong>:
          {!! $voucher->project->rawLink() !!}
        </p>

        <hr>
        <div class="col-sm-12">
  <br>
  @if ($voucher->measurements()->count())
    <a href="{{ url('vouchers/'. $voucher->id. '/measurements')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
        {{ $voucher->measurements()->count() }}
        @lang('messages.measurements')
    </a>
    &nbsp;&nbsp;
@else
    @can ('create', App\Models\Measurement::class)
    <a href="{{ url('vouchers/'. $voucher->id. '/measurements/create')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_measurements')
    </a>
    &nbsp;&nbsp;
@endcan
@endif
</div>
<div class="col-sm-12">
<br>
@can ('update', $voucher)
				<a href="{{ url('vouchers/'. $voucher->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
          @lang('messages.edit')
        </a>
        &nbsp;&nbsp;
@endcan


@can ('create', App\Models\Media::class)
    &nbsp;&nbsp;
      <a href="{{ url('vouchers/'. $voucher->id. '/media-create')  }}" class="btn btn-default">
      <i class="fa fa-btn fa-plus"></i>
      <i class="fas fa-photo-video"></i>
      <i class="fas fa-headphones-alt"></i>
      @lang('messages.create_media')
    </a>
@endcan


</div>

</div>
</div>

@if (isset($media))
  {!! View::make('media.index-model', ['model' => $voucher, 'media' => $media ]) !!}
@endif



</div>
</div>
@endsection
