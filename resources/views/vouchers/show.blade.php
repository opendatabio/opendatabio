@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.voucher')
		<div class="panel-body">
<p><strong>
@lang('messages.identification')
: </strong>
@if (is_null($identification))
        @lang ('messages.unidentified')
@else
    {!! $identification->rawLink() !!}
    </p>
    <p><strong>
    @lang('messages.identified_by')
:</strong>
    {!! $identification->person ? $identification->person->rawLink() : Lang::get('messages.not_registered') !!}
     ({{ $identification->formatDate }})
    </p>
    @if ($identification->herbarium_id)
    <p><strong>
    @lang('messages.identification_based_on')
:</strong>
    @lang('messages.voucher') {{ $identification->herbarium_reference }} /
    {!! $herbarium->herbarium->rawLink() !!}
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


@endif <!-- identification -->

@if ($voucher->parent instanceof App\Plant)
    <p><strong>
    @lang('messages.plant')
: </strong>
    {!! $voucher->parent->rawLink() !!}
    {{ $voucher->parent->location ? $voucher->parent->locationWithGeom->coordinatesSimple : ''}}
    </p>
    <p><strong>
    @lang('messages.location_precision')
:</strong>
    {{ $voucher->parent->location ? $voucher->parent->location->precision : '' }} <a data-toggle="collapse" href='#hintp'>?</a>
    </p>
@elseif ($voucher->parent instanceof App\Location)
    <p><strong>
    @lang('messages.location')
: </strong>
    {!! $voucher->parent->rawLink() !!}
    {{ $voucher->locationWithGeom->coordinatesSimple }}
    </p>
    <p><strong>
    @lang('messages.location_precision')
:</strong>
    {{ $voucher->parent->precision }} <a data-toggle="collapse" href='#hintp'>?</a>
    </p>
@else
    <p><strong>
    @lang('messages.voucher_parent_missing_error')
    </strong>
    </p>
@endif
<div id='hintp' class='panel-collapse collapse'>
    @lang('messages.location_precision_hint')
</div>

<p><strong>
@lang('messages.voucher_number')
:</strong>
{{ $voucher->number }}
</p>

@if ($voucher->herbaria->count())
<p><strong>
@lang('messages.voucher_herbaria')
:</strong>
<ul>
@foreach ($voucher->herbaria as $herb)
<li>{!! $herb->rawLink() !!} ({{$herb->pivot->herbarium_number}})</li>
@endforeach
</ul>
@endif

<p><strong>
@lang('messages.project')
:</strong>
{!! $voucher->project->rawLink() !!}
</p>

<p><strong>
@lang('messages.collection_date')
:</strong>
{{$voucher->formatDate}}
</p>

@if ($voucher->notes)
<p><strong>
@lang('messages.notes')
:</strong>
{{$voucher->notes}}
</p>
@endif

<p><strong>
@lang('messages.collectors')
:</strong><br>
@lang ('messages.main_collector')
:<ul>
    <li>{!! $voucher->person->rawLink() !!}</li>
</ul>
@if ($collectors->count())
@lang ('messages.additional_collectors')
    <ul>
    @foreach ($collectors as $collector)
    <li>{!! $collector->person->rawLink() !!}</li>
    @endforeach
    </ul>
@endif
</p>


@if ($voucher->measurements()->count())
<div class="col-sm-4">
    <a href="{{ url('vouchers/'. $voucher->id. '/measurements')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $voucher->measurements()->count() }}
@lang('messages.measurements')
    </a>
</div>
@else
    @can ('create', App\Measurement::class)
<div class="col-sm-4">
    <a href="{{ url('vouchers/'. $voucher->id. '/measurements/create')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_measurements')
    </a>
</div>
@endcan
@endif
<br><br>
@can ('update', $voucher)
			    <div class="col-sm-4">
				<a href="{{ url('vouchers/'. $voucher->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
@lang('messages.edit')

				</a>
			    </div>
@endcan
    @can ('create', App\Picture::class)
<div class="col-sm-4">
    <a href="{{ url('vouchers/'. $voucher->id. '/pictures/create')  }}" class="btn btn-success">
        <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_picture')
    </a>
</div>
 @endcan
                </div>
            </div>
</div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
@if ($voucher->pictures->count())
{!! View::make('pictures.index', ['pictures' => $voucher->pictures]) !!}
@endif
    </div>
@endsection
