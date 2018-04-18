@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Dashboard</div>

                <div class="panel-body">
<p>@lang ('messages.welcome_message', compact('nplants', 'nvouchers'))</p>

@if (Auth::user()->person)
<p>@lang ('messages.your_default_person'): 
    {!! Auth::user()->person->rawLink() !!}
</p>
@else
<p>@lang ('messages.no_default_person') 
    <a href="{{url('/selfedit')}}">@lang('messages.here')</a>
</p>
@endif
@if (Auth::user()->defaultProject)
<p>@lang ('messages.default_project'): 
    {!! Auth::user()->defaultProject->rawLink() !!}
</p>
@endif
@if (Auth::user()->defaultDataset)
<p>@lang ('messages.default_dataset'): 
    {!! Auth::user()->defaultDataset->rawLink() !!}
</p>
@endif

@if (Auth::user()->projects()->count())
<p><strong>@lang('messages.projects'):</strong></p>
<ul>
@foreach (Auth::user()->projects as $project)
    <li>{!! $project->rawLink() !!}
(@lang('levels.project.' . $project->pivot->access_level )
)</li>
@endforeach
    </ul>
@endif
@if (Auth::user()->datasets()->count())
<p><strong>@lang('messages.datasets'):</strong></p>
<ul>
@foreach (Auth::user()->datasets as $dataset)
    <li>{!! $dataset->rawLink() !!}
(@lang('levels.project.' . $dataset->pivot->access_level )
)</li>
@endforeach
    </ul>
@endif
@if (Auth::user()->forms()->count())
<p><strong>@lang('messages.forms'):</strong></p>
<ul>
@foreach (Auth::user()->forms as $form)
    <li>{!! $form->rawLink() !!}</li>
@endforeach
    </ul>
@endif




                </div>
            </div>
        </div>
    </div>
</div>
@endsection
