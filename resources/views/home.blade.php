@extends('layouts.app')

@section('content')
<div class="container">
    <div class="col-md-8 col-md-offset-2">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>@lang ('messages.welcome_message')</h4>
        </div>
        <div class="panel-body">
          <div class="col-sm-12">
          <p>@lang('messages.welcome_summary',compact('nplants', 'nvouchers','ndatasets','nprojects','nmeasurements'))</p>
          <br>
          @if (Auth::user()->person)
          <p>
            <strong>@lang ('messages.your_default_person')</strong>:
            {!! Auth::user()->person->rawLink() !!}
          </p>
        @else
          <p><strong>@lang ('messages.no_default_person')</strong>:
            <a href="{{url('/selfedit')}}">@lang('messages.here')</a>
          </p>
        @endif
        @if (Auth::user()->defaultProject)
          <p><strong>@lang ('messages.default_project')</strong>:
            {!! Auth::user()->defaultProject->rawLink() !!}
          </p>
        @endif

        @if (Auth::user()->defaultDataset)
          <p><strong>@lang ('messages.default_dataset')</strong>:
            {!! Auth::user()->defaultDataset->rawLink() !!}
          </p>
        @endif
        <br>
        <p>
          @if (Auth::user()->projects()->count())
          <a data-toggle="collapse" href="#myprojects" class="btn btn-default">@lang('messages.my_projects')</a>
          @endif

          @if (Auth::user()->datasets()->count())
            &nbsp;&nbsp;
            <a data-toggle="collapse" href="#mydatasets" class="btn btn-default">@lang('messages.my_datasets')</a>
          @endif

          @if (Auth::user()->forms()->count())
            &nbsp;&nbsp;
            <a data-toggle="collapse" href="#myforms" class="btn btn-default">@lang('messages.forms')</a>
          @endif
        </p>
        <p>
          @if(Auth::user())
            <a href="{{ url('token') }}" class="btn btn-default">
              @lang('messages.api_token')
            </a>
            &nbsp;&nbsp;
            <a href="{{ route('userjobs.index') }}" class="btn btn-default">
              @lang('messages.userjobs')
            </a>
            &nbsp;&nbsp;
            <a href="{{ route('selfedit') }}" class="btn btn-success">
              @lang('messages.edit_profile')
            </a>
          @endif
        </p>
      </div>
    </div>
    <div class="panel-collapse collapse" id='myprojects'>
      @if (Auth::user()->projects()->count())
        <div class="panel-heading">
          @lang('messages.my_projects')
        </div>
        <div class="panel-body">
            <ul>
              @foreach (Auth::user()->projects as $project)
                <li>{!! $project->rawLink() !!} (@lang('levels.project.' . $project->pivot->access_level ))</li>
              @endforeach
            </ul>
        </div>
      @endif
    </div>
    <div class="panel-collapse collapse" id='mydatasets'>
      @if (Auth::user()->datasets()->count())
      <div class="panel-heading">
        @lang('messages.datasets')
      </div>
      <div class="panel-body">
        <ul>
          @foreach (Auth::user()->datasets as $dataset)
            <li>{!! $dataset->rawLink() !!}  (@lang('levels.project.' . $dataset->pivot->access_level ))</li>
          @endforeach
        </ul>
      </div>
      @endif
    </div>
    <div class="panel-collapse collapse" id='myforms'>
      @if (Auth::user()->forms()->count())
      <div class="panel-heading">
        @lang('messages.forms')
      </div>
      <div class="panel-body">
          <ul>
            @foreach (Auth::user()->forms as $form)
              <li>{!! $form->rawLink() !!}</li>
            @endforeach
          </ul>
      </div>
      @endif
    </div>
    </div>
  </div>
</div>

@endsection
