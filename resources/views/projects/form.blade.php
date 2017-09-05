<div class="form-group">
    <label for="name" class="col-sm-3 control-label">
@lang('messages.name')
</label>
    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($project) ? $project->name : null) }}">
    </div>
</div>
<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($project) ? $project->notes : null) }}</textarea>
            </div>
</div>
<div class="form-group">
    <label for="privacy" class="col-sm-3 control-label">
@lang('messages.privacy')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('privacy', isset($project) ? $project->privacy : 0); ?>

	<select name="privacy" id="privacy" class="form-control" >
	@foreach (App\Project::PRIVACY_LEVELS as $level)
        <option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
@lang('levels.privacy.' . $level)
</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang('messages.project_privacy_hint')
    </div>
  </div>
</div>

<div class="form-group">
<label for="admins" class="col-sm-3 control-label">
@lang('messages.admins')
</label>
<a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
<div class="col-sm-6">
{!! Multiselect::select(
    'admins', 
    $fullusers->pluck('email', 'id'), isset($project) ? $project->admins->pluck('id') : [Auth::user()->id],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.collabs')
</label>
<div class="col-sm-6">
{!! Multiselect::select(
    'collabs', 
    $fullusers->pluck('email', 'id'), isset($project) ? $project->collabs->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.viewers')
</label>
<div class="col-sm-6">
{!! Multiselect::select(
    'viewers', 
    $allusers->pluck('email', 'id'), isset($project) ? $project->viewers->pluck('id') : [],
     ['class' => 'multiselect form-control']
) !!}
</div>
<div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.project_admins_hint')
    </div>
</div>
</div>
