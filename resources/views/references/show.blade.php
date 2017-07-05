@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.bibreference')
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
