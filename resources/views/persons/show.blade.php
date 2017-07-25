@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.person_details')
                </div>

		<div class="panel-body">
<div class="col-sm-6">
	<strong>
	@lang('messages.full_name')
	</strong>
</div>
<div class="col-sm-6">
	{{ $person->full_name }}
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.abbreviation')
	</strong>
</div>
<div class="col-sm-6">
	{{ $person->abbreviation }}
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.email')
	</strong>
</div>
<div class="col-sm-6">
	{{ $person->email }}
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.institution')
	</strong>
</div>
<div class="col-sm-6">

	{{ $person->institution }}
&nbsp;
</div>
<div class="col-sm-6">
	<strong>
	@lang('messages.herbarium')
	</strong>
</div>
<div class="col-sm-6">
@if ($person->herbarium)
<a href="{{url('herbaria/'. $person->herbarium->id)}}">{{ $person->herbarium->acronym }}</a>
@endif
&nbsp;
</div>

@if ($person->taxons->count())
<div class="col-sm-12">
	<strong>
	@lang('messages.specialist_in')
	</strong>
<ul>
@foreach ($person->taxons as $taxon)
<li><a href="{{url('taxons/'. $taxon->id)}}">{{ $taxon->name }}</a></li>
@endforeach
</ul>
&nbsp;
</div>
@endif
@can ('update', $person)
				<a class="btn btn-success" href="{{url ('persons/' . $person->id . '/edit')}}">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit_person')
				</a>
@endcan
            </div>
<!-- Other details (specialist, collects, etc?) -->
        </div>
    </div>
@endsection
