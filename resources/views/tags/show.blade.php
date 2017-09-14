@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.tags')
                </div>
		<div class="panel-body">
		    <p><strong>
@lang('messages.name')
: </strong> {{ $tag->translation }} </p>
@can ('update', $tag)
		    <a href="{{ url('tags/'.$tag->id.'/edit') }}" class="btn btn-success">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')
				</a>
@endcan
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.datasets')
                </div>

		<div class="panel-body">
                        <table class="table table-striped person-table">
                            <thead>
                                <th>
@lang('messages.name')
</th>
                            </thead>
                            <tbody>
                                @foreach ($tag->datasets as $dataset)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('datasets/'.$dataset->id) }}">{{ $dataset->name }}</a>
					</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
@endsection
