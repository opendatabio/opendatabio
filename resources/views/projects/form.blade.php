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
    <!-- already admins -->
    <span id = "admins-ul">
         @if (isset($project))
         @foreach ($project->admins as $admin)
         <span class="multipleSelector">
          <input type="hidden" name="admins[]" value="{{ $admin->id  }}" />
          {{$admin->email}}
         </span>
         @endforeach
         @else <!-- isset project -->
         <span class="multipleSelector">
          <input type="hidden" name="admins[]" value="{{ Auth::user()->id  }}" />
          {{Auth::user()->email}}
         </span>
         @endif <!-- isset project -->

    </span>
    <select name="admins-ms" id="admins-ms" class="form-control multi-select">
        <option value='' >&nbsp;</option>
        @foreach ($users as $user)
        <option value="{{$user->id}}" >{{ $user->email }}</option>
        @endforeach
    </select>
</div>
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.collabs')
</label>
<div class="col-sm-6">
    <!-- already collabs -->
    <span id = "collabs-ul">
         @if (isset($project))
         @foreach ($project->collabs as $collab)
         <span class="multipleSelector">
          <input type="hidden" name="collabs[]" value="{{ $collab->id  }}" />
          {{$collab->email}}
         </span>
         @endforeach
         @endif
    </span>
    <select name="collabs-ms" id="collabs-ms" class="form-control multi-select">
        <option value='' >&nbsp;</option>
        @foreach ($users as $user)
        <option value="{{$user->id}}" >{{ $user->email }}</option>
        @endforeach
    </select>
</div>
</div><div class="form-group">
<label for="collabs" class="col-sm-3 control-label">
@lang('messages.viewers')
</label>
<div class="col-sm-6">
    <!-- already viewers -->
    <span id = "viewers-ul">
         @if (isset($project))
         @foreach ($project->viewers as $viewer)
         <span class="multipleSelector">
          <input type="hidden" name="viewers[]" value="{{ $viewer->id  }}" />
          {{$viewer->email}}
         </span>
         @endforeach
         @endif
    </span>
    <select name="viewers-ms" id="viewers-ms" class="form-control multi-select">
        <option value='' >&nbsp;</option>
        @foreach ($users as $user)
        <option value="{{$user->id}}" >{{ $user->email }}</option>
        @endforeach
    </select>
</div>
<div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.project_admins_hint')
    </div>
</div>
</div><!-- form group -->
