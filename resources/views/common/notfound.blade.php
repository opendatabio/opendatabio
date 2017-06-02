@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        @lang('messages.whoops')
      </h4>
    </div>
      <div class="panel-body">
	@lang('messages.notfound')
    </div>
  </div>

@endsection
