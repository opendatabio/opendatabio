@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_project')
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

@if (isset($project))
		    <form action="{{ url('projects/' . $project->id)}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('projects')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
@endif
		     {{ csrf_field() }}

<div class="form-group">
    <label for="name" class="col-sm-3 control-label mandatory">
@lang('messages.name')
</label>
    <a data-toggle="collapse" href="#project_name_hint" class="btn btn-default">?</a>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($project) ? $project->name : null) }}">
    </div>
    <div class="col-sm-12">
      <div id="project_name_hint" class="panel-collapse collapse">
         @lang('messages.project_name_hint')
       </div>
     </div>
</div>


<div class="form-group">
    <label for="title" class="col-sm-3 control-label" id='titlelabel'>
      @lang('messages.title')
    </label>
    <a data-toggle="collapse" href="#project_title_hint" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <input type="text" name="title" id="title" class="form-control" value="{{ old('title', isset($project) ? $project->title : null) }}" maxlength="191">
    </div>
    <div class="col-sm-12">
      <div id="project_title_hint" class="panel-collapse collapse">
         @lang('messages.project_title_hint')
       </div>
     </div>
</div>

<div class="form-group">
    <label for="description" class="col-sm-3 control-label">
      @lang('messages.dataset_short_description')
    </label>
    <a data-toggle="collapse" href="#dataset_short_description" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <textarea name="description" id="description" class="form-control" maxlength="500">{{ old('description', isset($project) ? $project->description : null) }}</textarea>
    </div>
    <div class="col-sm-12">
      <div id="dataset_short_description" class="panel-collapse collapse">
  	@lang('messages.project_short_description_hint')
      </div>
    </div>
</div>


<div class="form-group">
    <label for="details" class="col-sm-3 control-label">
      @lang('messages.project_details')
    </label>
    <a data-toggle="collapse" href="#dataset_details" class="btn btn-default">?</a>
    <div class="col-sm-6">
      <textarea name="details" id="details" class="form-control">{{ old('details', isset($project) ? $project->details : null) }}</textarea>
    </div>
    <div class="col-sm-12">
      <div id="dataset_details" class="panel-collapse collapse">
  	@lang('messages.project_details_hint')
      </div>
    </div>
</div>


<div class="form-group">
  <label for="logo" class="col-sm-3 control-label ">
      @lang('messages.logo')
  </label>
  <a data-toggle="collapse" href="#logo_hint" class="btn btn-default">?</a>
  <div class="col-sm-4">
    <input type="file" name="logo" id="logo"  >
  </div>
  <div class="col-sm-2">
    @if(isset($logoUrl))
      <div class="float-right">
        <img src='{{ $logoUrl }}' width='100'>
      </div>
    @endif
  </div>
  <div class="col-sm-12">
    <div id="logo_hint" class="panel-collapse collapse">
@lang('messages.logo_project_hint')
    </div>
  </div>
</div>



<div class="form-group">
  <label for="tags" class="col-sm-3 control-label ">
      @lang('messages.tags')
  </label>
  <a data-toggle="collapse" href="#tags_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    {!! Multiselect::select(
        'tags',
        $tags->pluck('name', 'id'), isset($project) ? $project->tags->pluck('id') : [],
         ['class' => 'multiselect form-control']
    ) !!}
  </div>
  <div class="col-sm-12">
    <div id="tags_hint" class="panel-collapse collapse">
@lang('messages.tags_hint')
    </div>
  </div>
</div>


<hr>

<div class="form-group">
<label for="admins" class="col-sm-3 control-label mandatory">
@lang('messages.admins')
</label>
<a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'admins',
    $fullusers->pluck('email', 'id'), isset($project) ? $project->admins->pluck('id') : [Auth::user()->id],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div>
<div class="col-sm-12">
  <div id="hint2" class="panel-collapse collapse">
     <br>
     @lang('messages.project_admins_hint')
     <br>
   </div>

</div>

<div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.collabs')
</label>
<div class="col-sm-6">
{!! Multiselect::autocomplete(
    'collabs',
    $fullusers->pluck('email', 'id'), isset($project) ? $project->collabs->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div>

<div class="form-group" id='project_viewers'  >
  <label for="collabs" class="col-sm-3 control-label">
    @lang('messages.viewers')
  </label>
  <div class="col-sm-6">
    {!! Multiselect::autocomplete(
      'viewers',
      $allusers->pluck('email', 'id'), isset($project) ? $project->viewers->pluck('id') : [],
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
<script>
$(document).ready(function() {

/* show or hide elements depending on type of privacy */
$('#privacy').on('change',function() {
    var privacy = $('#privacy option:selected').val();
    if (privacy == {{ App\Models\Dataset::PRIVACY_AUTH}}) {
        $('#project_viewers').show();
        $('#licenselabel').removeClass('mandatory');

        //$('#titlelabel').removeClass('mandatory');
        //$('#authorslabel').removeClass('mandatory');
    } else {
        //$('#authorslabel').addClass('mandatory');
        $('#licenselabel').addClass('mandatory');
        //$('#titlelabel').addClass('mandatory');
        $('#project_viewers').hide();
    }
});


/* if license is different than public domain, title and authors must be informed */
/* and sui generis database policies may be included */
$('#license').on('change',function() {
    var license = $('#license option:selected').val();
    if (license == "CC0") {
        $('#titlelabel').removeClass('mandatory');
        $('#authorslabel').removeClass('mandatory');
        $('#policy').val(null);
        $('#show_policy').hide();
    } else {
        $('#authorslabel').addClass('mandatory');
        $('#titlelabel').addClass('mandatory');
        $('#show_policy').show();
    }
});


});

</script>


{!! Multiselect::scripts('authors', url('persons/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('admins', url('users/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('collabs', url('users/autocomplete'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
{!! Multiselect::scripts('viewers', url('users/autocomplete_all'), ['noSuggestionNotice' => Lang::get('messages.noresults')]) !!}
@endpush
