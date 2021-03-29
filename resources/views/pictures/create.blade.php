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
    <div class="col-sm-12">
    <p><strong>
@lang('messages.picture_of')
:</strong> {!! $object->fullname !!}
    </p>
    <input type="hidden"  name="object_id" value="{{$object->id}}">
    <input type="hidden"  name="object_type" value="{{get_class($object)}}">
  </div>
  </div>

@if (isset($picture))
  <div class="col-sm-12">
    <img src = "{{$picture->url(true)}}" class="picture">
  </div>
@else
<div class="form-group">
  <div class="col-sm-12">
    <label for="trait_id" class="col-sm-3 control-label mandatory">
      @lang('messages.picture')
    </label>
    <div class="col-sm-6">
    <input type="file" name="image" id="image" class="form-control" accept="image/*">
    </div>
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
        <td><input name="description[{{$language->id}}]" value="{{ old('description.' . $language->id, isset($picture) ? $picture->translate(\App\Models\UserTranslation::DESCRIPTION, $language->id) : null) }}"></td>
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


<!-- license object must be an array with CreativeCommons license codes applied to the model --->
<div class="form-group" id='creativecommons' >
    <label for="license" class="col-sm-3 control-label mandatory">
      @lang('messages.public_license')
    </label>
    <a data-toggle="collapse" href="#creativecommons_pictures_hint" class="btn btn-default">?</a>
    <div class="col-sm-6">
      @php
        $currentlicense = "CC-BY";
        $currentversion = config('app.creativecommons_version')[0];
        if (isset($picture)) {
           if (null != $picture->license) {
             $license = explode(' ',$picture->license);
             $currentlicense = $license[0];
             $currentversion = $license[1];
           }
        }
        $oldlicense = old('license', $currentlicense);
        $oldversion = old('version',$currentversion);
        $readonly = null;
        if (count(config('app.creativecommons_version'))==1) {
          $readonly = 'readonly';
        }
      @endphp
      <select name="license" id="license" class="form-control" >
        @foreach (config('app.creativecommons_licenses') as $level)
          <option value="{{ $level }}" {{ $level == $oldlicense ? 'selected' : '' }}>
            {{$level}} - @lang('levels.' . $level)
          </option>
        @endforeach
      </select>
      <strong>version:</strong>
      @if (null != $readonly)
        <input type="hidden" name="license_version" value=" {{ $oldversion }}">
        {{ $oldversion }}
      @else
      <select name="license_version" class="form-control" {{ $readonly }}>
        @foreach (config('app.creativecommons_version') as $version)
          <option value="{{ $version }}" {{ $version == $oldversion ? 'selected' : '' }}>
            {{ $version}}
          </option>
        @endforeach
      </select>
      @endif
    </div>
    <div class="col-sm-12">
      <div id="creativecommons_pictures_hint" class="panel-collapse collapse">
        <br>
        @lang('messages.creativecommons_picture_hint')
      </div>
    </div>
</div>

<div class="form-group">
<label for="tags" class="col-sm-3 control-label">
@lang('messages.tags')
</label>
<a data-toggle="collapse" href="#tags_hint" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::select(
    'tags',
    $tags->pluck('name', 'id'), isset($picture) ? $picture->tags->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
  <div id="tags_hint" class="col-sm-12 collapse">
    @lang('messages.tags_hint')
  </div>
</div>


<div class="form-group" >
  <label for="date" class="col-sm-3 control-label">@lang('messages.date')</label>
  <a data-toggle="collapse" href="#date_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="date" name="date" value="{{ old('date',isset($picture) ? $picture->date : null) }}" min="1600-01-01" max={{today()}}>
  </div>
  <div id="date_hint" class="col-sm-12 collapse">
    @lang('messages.picture_date_hint')
  </div>
</div>


<div class="form-group">
<label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
<div class="col-sm-6">
  <textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($picture) ? $picture->notes : null) }}</textarea>
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
