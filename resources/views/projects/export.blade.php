@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
          <div class="panel panel-default">
            <div class="panel-heading">
              @lang('messages.project')
              @if(isset($project))
                : <strong>{{$project->name}}</strong>
              @endif
            </div>
            @php
              $lockimage= '<i class="fas fa-lock"></i>';
              if ($project->privacy != App\Project::PRIVACY_AUTH) {
                $license = explode(" ",$project->license);
                $license_logo = 'images/'.mb_strtolower($license[0]).".png";
              } else {
                $license_logo = 'images/cc_srr_primary.png';
              }
              if ($project->privacy == App\Project::PRIVACY_PUBLIC) {
                $lockimage= '<i class="fas fa-lock-open"></i>';
              }
            @endphp
            <div class="panel-body">
              <h3>
                {{$project->title}}
              </h3>
              @if (isset($project->description))
              <p>
                {{ $project->description }}
              </p>
              @endif
              <br>
              <p>
                <a href="http://creativecommons.org/license" target="_blank">
                  <img src="{{ asset($license_logo) }}" alt="{{ $project->license }}" width=100>
                </a>
                {!! $lockimage !!} @lang('levels.privacy.'.$project->privacy)
              </p>

              @if (isset($project->citation))
                <br>
                <p>
                  <strong>@lang('messages.howtocite')</strong>:
                  <br>
                  {!! $project->citation !!}  <a data-toggle="collapse" href="#bibtex" class="btn-sm btn-primary">BibTeX</a>
                </p>
                <div id='bibtex' class='panel-collapse collapse'>
                  <pre><code>{{ $project->bibtex }}</code></pre>
                </div>
              @endif

              @if ($project->policy)
                <br>
                <p>
                  <strong>
                    @lang('messages.data_policy')
                  </strong>:
                  <br>
                  {{$project->policy}}
                </p>
                <br>
              @endif

              <p>
                <strong>
                  @lang('messages.admins')</strong>:
                  <ul>
                    @foreach ($project->users()->wherePivot('access_level', '=', App\Project::ADMIN)->get() as $admin)
                      @php
                      if ($admin->person->fullname) {
                        $adm = $admin->person->fullname." ".$admin->email;
                      } else {
                        $adm = $admin->email;
                      }
                      @endphp
                      <li> {{ $adm }} </li>
                    @endforeach
                  </ul>
              </p>

</div>
</div>


<!--- DATASET REQUEST PANEL -->
<div class="panel panel-default" id='dataset_request_panel' >
  <div class="panel-heading">
    @lang('messages.data_request')
    &nbsp;&nbsp;<a data-toggle="collapse" href="#project_request_hint" class="btn btn-default">?</a>
    <div id="project_request_hint" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.project_request_hint')
      </div>
  </div>
  </div>
  <div class="panel-body">

  <form action="{{ url('projects/'.$project->id.'/emailrequest')}}" method="POST" class="form-horizontal" id='project_request_form'>
  <!-- csrf protection -->
    {{ csrf_field() }}

@if (!Auth::user())
  <div class="form-group">
    <label class="col-sm-3 control-label mandatory">
      @lang('messages.email')
    </label>
    <div class="col-sm-7">
      <input type="email" name="email" value="" >
    </div>
  </div>
@endif

    <div class="form-group">
      <label class="col-sm-3 control-label mandatory">
        @lang('messages.dataset_request_use')
      </label>
      <div class="col-sm-7">
          <input type="radio" name="dataset_use_type" value="@lang('messages.dataset_request_use_exploratory')" required >&nbsp;@lang('messages.dataset_request_use_exploratory')
          <input type="radio" name="dataset_use_type" value="@lang('messages.dataset_request_use_inpublications')" required >&nbsp;@lang('messages.dataset_request_use_inpublications')
      </div>
     </div>

    <div class="form-group">
      <label class="col-sm-3 control-label mandatory">
        @lang('messages.description')
      </label>
      <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
      <div class="col-sm-7">
        <textarea name="dataset_use_description" required></textarea>
      </div>
      <div class="col-sm-12">
        <div id="hint6" class="panel-collapse collapse">
	         @lang('messages.dataset_request_use_description_hint')
         </div>
       </div>
     </div>
      <div class="form-group">
			<div class="col-sm-offset-3 col-sm-6">
        <span id='submitting' hidden><i class="fas fa-sync fa-spin"></i></span>
        <input id='submit' class="btn btn-success" name="submit" type='submit' value='@lang('messages.submit')' >
        </div>
			</div>
</form>

</div>
</div>
<!-- END DATASET REQUEST FORM
@endsection
