@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_picture')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')
<div id="ajax-error" class="collapse alert alert-danger">
@lang('messages.whoops')
</div>

@if (isset($picture))
		    <form action="{{ url('pictures/' . $picture->id)}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
{{ method_field('PUT') }}
@else
		    <form action="{{ url('pictures')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
@endif

<!-- csrf protection -->
{{ csrf_field() }}
<div class="form-group">
    <p><strong>
@lang('messages.picture_of')
:</strong> {!! $object->fullname !!}
    </p>
    <input type="hidden"  name="object_id" value="{{$object->id}}">
    <input type="hidden"  name="object_type" value="{{get_class($object)}}">
</div>
@if (isset($picture))
    <img src = "{{$picture->url(true)}}" class="picture">
@else
<div class="form-group">
    <label for="trait_id" class="col-sm-3 control-label mandatory">
@lang('messages.picture')
</label>
    <div class="col-sm-6">
    <input type="file" name="image" id="image" class="form-control" accept="image/*">
    </div>
</div>
@endif

<div class="form-group">
    <div class="col-sm-12">
<table class="table table-striped">
<thead>
    <th>
@lang('messages.language')
    </th>
    <th>
@lang('messages.description')
    </th>
</thead>
<tbody>
@foreach ($languages as $language) 
    <tr>
        <td>{{$language->name}}</td>
        <td><input name="description[{{$language->id}}]" value="{{ old('description.' . $language->id, isset($picture) ? $picture->translate(\App\UserTranslation::DESCRIPTION, $language->id) : null) }}"></td>
    </tr>
@endforeach
    <tr>
</tbody>
</table>
    </div>
</div>

<div class="form-group">
    <label for="person_id" class="col-sm-3 control-label mandatory">
@lang('messages.credits')
</label>
        <a data-toggle="collapse" href="#hintp" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::autocomplete('collector', 
    $persons->pluck('abbreviation', 'id'), 
    isset($picture) ? $picture->collectors->pluck('person_id') : 
    (empty(Auth::user()->person_id) ? '' : [Auth::user()->person_id] )
, 
    ['class' => 'multiselect form-control']) 
!!}
</div>
</div>

<div class="form-group">
<label for="tags" class="col-sm-3 control-label">
@lang('messages.tags')
</label>
<div class="col-sm-6">
{!! Multiselect::select(
    'tags', 
    $tags->pluck('name', 'id'), isset($picture) ? $picture->tags->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
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
@push ('scripts')
{!! Multiselect::scripts('collector', url('persons/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
@endpush
