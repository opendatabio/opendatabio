@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" href="#help" class="btn btn-default">
@lang('messages.help')
</a>
      </h4>
    </div>
    <div id="help" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.datasets_hint')
      </div>
    </div>
  </div>

@can ('create', App\Dataset::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.create_dataset')
                </div>

                <div class="panel-body">
                <a href="{{url('datasets/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

@if ($mydatasets)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.my_datasets')
                </div>

                <div class="panel-body">
<ul>
@foreach ($mydatasets as $dataset)
    <li><a href="{{url('datasets/' . $dataset->id)}}">{{$dataset->name}}</a>
(@lang('levels.project.' . $dataset->pivot->access_level )
)</li>
@endforeach
    </ul>
                </div>
            </div>
@endif

            <!-- Registered Datasets -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.datasets')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.name')
</th>
                                <th>
@lang('messages.members')
</th>
                                <th>
@lang('messages.data_privacy')
</th>
                                <th>
@lang('messages.tags')
</th>
			    </thead>
<tbody>
                                @foreach ($datasets as $dataset)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('datasets/'.$dataset->id) }}">{{ $dataset->name }}</a>
					</td>
                                        <td class="table-text">
                                        @foreach ($dataset->users as $user)
                                        {{ $user->email }} <br/>
                                        @endforeach
                                        </td>
<td>
@lang ('levels.privacy.' . $dataset->privacy)
</td>
                                        <td class="table-text">
                                        @foreach ($dataset->tags as $tag)
                                        <a href="{{url('tags/'. $tag->id)}}">{{ $tag->translation }}</a>
<br/>
                                        @endforeach
                                        </td>
                                    </tr>
				    @endforeach
				    </tbody>
                        </table>
 {{ $datasets->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection
