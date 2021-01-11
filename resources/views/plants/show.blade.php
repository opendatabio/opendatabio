@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.plant')
                    <span class="history" style="float:right">
                    <a href="{{url("plants/$plant->id/activity")}}">
                    @lang ('messages.see_history')
                    </a>
                    </span>
                </div>
		<div class="panel-body">
<p><strong>
@lang('messages.identification')
: </strong>
@if (is_null($identification))
        @lang ('messages.unidentified')
@else
{!! $identification->rawLink(); !!}
    </p>
    <p><strong>
    @lang('messages.identified_by')
:</strong>
@if ($identification->person)
{!! $identification->person->rawLink() !!} ({{ $identification->formatDate }})
@else
    @lang('messages.not_registered')
@endif
    </p>
    @if ($identification->herbarium_id)
    <p><strong>
    @lang('messages.identification_based_on')
:</strong>
        @lang('messages.voucher') {{ $identification->herbarium_reference }} &#64;
    {!! $identification->herbarium->rawLink() !!}
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

<p><strong>
@lang('messages.location')
: </strong>
@if($plant->location)
{!! $plant->location->rawLink() !!} {!! $plant->locationWithGeom->coordinatesSimple !!}
@else
    Unknown location
@endif
@if ($plant->x)
    @if ($plant->location->adm_level == 999)
(@lang('messages.angle'): {{$plant->angle}}, @lang('messages.distance'): {{$plant->distance}})
    @else
(X: {{$plant->x}}, Y: {{$plant->y}})
    @endif
@endif
</p>
@if ($plant->location)
    <p><strong>
    @lang('messages.location_precision')
:</strong>
    {!! $plant->location->precision !!} <a data-toggle="collapse" href='#hintp'>?</a>
    </p>
@endif
<div id='hintp' class='panel-collapse collapse'>
    @lang('messages.location_precision_hint')
</div>

<p><strong>
@lang('messages.plant_tag')
:</strong>
{{ $plant->tag }}
</p>

<p><strong>
@lang('messages.project')
:</strong>
{!! $plant->project->rawLink() !!}
</p>

<p><strong>
@lang('messages.tag_date')
:</strong>
{{$plant->formatDate}}
</p>

@if ($plant->notes)
<p><strong>
@lang('messages.notes')
:</strong>
{{$plant->notes}}
</p>
@endif

<p><strong>
@lang('messages.tag_team')
:</strong>
@if ($collectors->count())
    <ul>
    @foreach ($collectors as $collector)
    <li>{!! $collector->person->rawLink() !!}</li>
    @endforeach
    </ul>
@else
    @lang('messages.not_registered')
@endif
</p>
@if ($plant->measurements()->withoutGlobalScopes()->count())
<div class="col-sm-4">
    <a href="{{ url('measurements/'. $plant->id. '/plant')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $plant->measurements()->withoutGlobalScopes()->count() }}
@lang('messages.measurements')
    </a>
</div>
@else
    @can ('create', App\Measurement::class)
<div class="col-sm-4">
    <a href="{{ url('plants/'. $plant->id. '/measurements/create')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
@lang('messages.create_measurements')
    </a>
</div>
@endcan
@endif
@if ($plant->vouchers()->count())
<div class="col-sm-4">
    <a href="{{ url('plants/'. $plant->id. '/vouchers')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
{{ $plant->vouchers()->count() }}
@lang('messages.vouchers')
    </a>
</div>
@else
@can ('create', App\Voucher::class)
<div class="col-sm-4">
<a href="{{url ('plants/' . $plant->id . '/vouchers/create')}}" class="btn btn-success">
    <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_voucher')

</a>
</div>
@endcan
@endif
<br><br>
@can ('update', $plant)
			    <div class="col-sm-3">
				<a href="{{ url('plants/'. $plant->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
@lang('messages.edit')

				</a>
			    </div>
@endcan
    @can ('create', App\Picture::class)
<div class="col-sm-3">
    <a href="{{ url('plants/'. $plant->id. '/pictures/create')  }}" class="btn btn-success">
        <i class="fa fa-btn fa-plus"></i>
@lang('messages.create_picture')
    </a>
</div>
 @endcan
                </div>
            </div>
@if ($plant->pictures->count())
{!! View::make('pictures.index', ['pictures' => $plant->pictures]) !!}
@endif
    </div>
@endsection
