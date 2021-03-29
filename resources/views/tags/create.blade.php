@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">
@lang('messages.help')
</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.hint_tag_create')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_tag')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($tag))
		    <form action="{{ url('tags/' . $tag->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('tags')}}" method="POST" class="form-horizontal">
@endif
		     {{ csrf_field() }}

<div class="form-group">
    <div class="col-sm-12">
<table class="table table-striped">
<thead>
    <th>
@lang('messages.language')
    </th>
    <th class="mandatory">
@lang('messages.tag_name')
    </th>
    <th>
@lang('messages.description')
    </th>
</thead>
<tbody>
@foreach ($languages as $language)
    <tr>
        <td>{{$language->name}}</td>
        <td><input name="name[{{$language->id}}]" value="{{ old('name.' . $language->id, isset($tag) ? $tag->translate(\App\Models\UserTranslation::NAME, $language->id) : null) }}"></td>
        <td><input name="description[{{$language->id}}]" value="{{ old('description.' . $language->id, isset($tag) ? $tag->translate(\App\Models\UserTranslation::DESCRIPTION, $language->id) : null) }}"></td>
    </tr>
@endforeach
    <tr>
</tbody>
</table>
    </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')

				</button>
				<a href="{{url()->previous()}}" class="btn btn-warning">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.back')
				</a>
			    </div>
			</div>
		    </form>
        </div>
    </div>
@endsection
