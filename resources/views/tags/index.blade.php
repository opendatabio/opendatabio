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
@lang('messages.tags_hint')
      </div>
    </div>
  </div>

@can ('create', App\Tag::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.create_tag')
                </div>

                <div class="panel-body">
                <a href="{{url('tags/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.tags')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.name')
</th>
			    </thead>
<tbody>
                                @foreach ($tags as $tag)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('tags/'.$tag->id) }}">{{ $tag->translation }}</a>
					</td>
                                    </tr>
				    @endforeach
				    </tbody>
                        </table>
 {{ $tags->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection
