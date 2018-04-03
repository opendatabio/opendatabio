@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.form')
</div>
		<div class="panel-body">
		    <p><strong>
@lang('messages.name')
: </strong>  {{ $form->name }} </p>

<p><strong>
@lang('messages.user')
: </strong>  {{ $form->user->email }} </p>

<p><strong>
@lang('messages.form_type')
: </strong> @lang('classes.'. $form->measured_type )</p>

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
</div>
<!-- Other details (specialist, herbarium, collects, etc?) -->
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.fill_form')
                </div>
            <div class="panel-body">
		    <form action="{{ url('forms/' . $form->id . '/prepare')}}" method="POST" class="form-horizontal">
		     {{ csrf_field() }}
<div class="form-group">
<label for="form_for" class="col-sm-3 control-label mandatory">
@lang('messages.fill_form_for')
</label>
	    <div class="col-sm-6">
	<select name="project_id" id="project_id" class="form-control">
	@foreach ( Auth::user()->projects as $project )
		<option value="{{$project->id}}" >
            {{ $project->name }}
		</option>
	@endforeach
	</select>
            </div>
</div>

<div class="form-group">
	    <div class="col-sm-6 col-sm-offset-3">
<input type="checkbox" name="clear">
@lang('messages.clear')
            </div>
</div>

		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.fill')
				</button>
			    </div>
			</div>
            </form>




            </div>
        </div>
</div>

@endsection
