@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.form')
		<div class="panel-body">
		    <p><strong>
@lang('messages.name')
: </strong>  {{ $form->name }} </p>

<p><strong>
@lang('messages.user')
: </strong>  {{ $form->user->email }} </p>
</p>

<p><strong>
@lang('messages.form_type')
: </strong> @lang('classes.'. $form->measured_type )</p>
<ul>
</ul>
</p>

@if ($form->notes) 
		    <p><strong>
@lang('messages.notes')
: </strong> {{$form->notes}}
</p>
@endif

<p><strong>
@lang('messages.traits')
:</strong>
<ul>
@foreach ($form->traits as $odbtrait)
<li><a href="{{url('traits/'.$odbtrait->id)}}">{{ $odbtrait->name }}</a></li>
@endforeach
</ul>
</p>

@can ('update', $form)
			    <div class="col-sm-6">
				<a href="{{ url('forms/'. $form->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>
            </div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
    </div>
@endsection
