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
@lang('messages.traits_hint')
      </div>
    </div>
  </div>

@can ('create', App\ODBTrait::class)
            <div class="panel panel-default">
                <div class="panel-heading">

                    @lang('messages.create_trait')
                </div>

                <div class="panel-body">
                <a href="{{url('traits/create')}}" class="btn btn-success">
@lang ('messages.create')
                </a>
                </div>
            </div>
@endcan

            <!-- Registered traits -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.traits')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.name')
</th>
                                <th>
@lang('messages.type')
</th>
                                <th>
@lang('messages.detail')
</th>
			    </thead>
<tbody>
                                @foreach ($traits as $mytrait)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('traits/'.$mytrait->id) }}">{{ $mytrait->name }}</a>
					</td>
                                        <td class="table-text">
@lang('levels.traittype.' . $mytrait->type)
                                        </td>
<td>
<?php
    switch ($mytrait->type) {
    case 0:
    case 1:
        echo $mytrait->unit;
        break;
    case 2:
    case 3:
    case 4:
        $cats = $mytrait->categories;
        echo $cats[0]->name . ', ' . $cats[1]->name . ', ...';
        break;
    }
?>

</td>
                                    </tr>
				    @endforeach
				    </tbody>
                        </table>
 {{ $traits->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection
