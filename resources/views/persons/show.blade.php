@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.person_details')
                    <span class="history" style="float:right">
                    <a href="{{url("persons/$person->id/history")}}">
                    @lang ('messages.see_history')
                    </a>
                    </span>
                </div>

		<div class="panel-body">
  <p>
  	<strong>
  	@lang('messages.full_name')
  :</strong>
  	{{ $person->full_name }}
  </p>
<p>
	<strong>
	@lang('messages.abbreviation')
:</strong>
	{{ $person->abbreviation }}
</p>
<p>
	<strong>
	@lang('messages.email')
:</strong>
	{{ $person->email }}
</p>
@if ($person->institution)
<p>
	<strong>
	@lang('messages.institution')
:</strong>
	{{ $person->institution }}
</p>
@endif
@if ($person->herbarium)
<p>
	<strong>
	@lang('messages.herbarium')
:</strong>
{!! $person->herbarium->rawLink() !!}
@endif
</p>

@if ($person->notes)
		    <p><strong>
@lang('messages.notes')
: </strong> {{$person->notes}}
</p>
@endif

@if ($person->taxons->count())
<p>
	<strong>
	@lang('messages.specialist_in')
	</strong>
<ul>
@foreach ($person->taxons as $taxon)
<li> {!! $taxon->rawLink() !!} </li>
@endforeach
</ul>
</p>
@endif
<div class="col-sm-3">
    <a href="{{ url('persons/'. $person->id. '/plants')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
@lang('messages.plants')
    </a>
</div>
<div class="col-sm-4">
    <a href="{{ url('persons/'. $person->id. '/vouchers')  }}" class="btn btn-default">
        <i class="fa fa-btn fa-search"></i>
@lang('messages.vouchers')
    </a>
</div>
@can ('update', $person)
				<a class="btn btn-success" href="{{url ('persons/' . $person->id . '/edit')}}">
@lang('messages.edit_person')
				</a>
@endcan
            </div>
</div>
    </div>
@endsection
