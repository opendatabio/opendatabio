@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.bibreference')
<span class="history" style="float:right">
<a href="{{url("references/$reference->id/history")}}">
@lang ('messages.see_history')
</a>
</span>
                </div>

                <div class="panel-body">
<div class="col-sm-6">
<strong>
@lang('messages.authors')
</strong>
</div>
<div class="col-sm-6">
{{ $reference->author }} &nbsp;
</div>
<div class="col-sm-6">
<strong>
@lang('messages.title')
</strong>
</div>
<div class="col-sm-6">
{{ $reference->title }} &nbsp;
</div>
<div class="col-sm-6">
<strong>
@lang('messages.year')
</strong>
</div>
<div class="col-sm-6">
{{ $reference->year }} &nbsp;
</div>

@if ($reference->doi)
<div class="col-sm-6">
<strong>
@lang('messages.doi')
</strong>
</div>
<div class="col-sm-6">
<a href="https://dx.doi.org/{{ $reference->doi }}">{{$reference->doi}}</a>&nbsp;
</div>
@else
@if ($reference->url)
<div class="col-sm-6">
<strong>
@lang('messages.externallink')
</strong>
</div>
<div class="col-sm-6">
<a href="{{ $reference->url}}">{{$reference->url}}</a>&nbsp;
</div>
@endif
@endif

<div class="col-sm-6">
<strong>
@lang('messages.bibtex_entry')
</strong>
</div>
<div class="col-sm-6">
{{ $reference->bibtex }} &nbsp;
</div>
@can ('update', $reference)
<div class="col-sm-6">
<a href="{{url('references/' . $reference->id . '/edit')}}" class="btn btn-success">
	<i class="fa fa-btn fa-plus"></i>
	@lang('messages.edit')
</a>
</div>
@endcan
                </div>
            </div>
<!-- Other details (whatever links to a Reference) -->
        </div>
    </div>
@endsection
