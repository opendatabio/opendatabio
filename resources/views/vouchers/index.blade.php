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
@lang('messages.vouchers_hint')
      </div>
    </div>
  </div>

            <!-- Registered Vouchers -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        @lang('messages.vouchers')
                    </div>

                    <div class="panel-body">
                        <table class="table table-striped" id="references-table">
                            <thead>
                                <th>
@lang('messages.collector_and_number')
</th>
                                <th>
@lang('messages.identification')
</th>
			    </thead>
<tbody>
                                @foreach ($vouchers as $voucher)
                                    <tr>
					<td class="table-text">
					<a href="{{ url('vouchers/'.$voucher->id) }}">{{ $voucher->fullname }}</a>
					</td>
                                        <td class="table-text">
                                            @if ($voucher->identification)
                                            <em>{{ $voucher->identification->taxon->fullname }}</em>
                                            @elseif ($voucher->parent and $voucher->parent->identification)
                                            <em>{{ $voucher->parent->identification->taxon->fullname }}</em>
                                            @else
                                            @lang('messages.unidentified')
                                            @endif
                                        </td>
                                    </tr>
				    @endforeach
				    </tbody>
                        </table>
 {{ $vouchers->links() }}
                    </div>
                </div>
        </div>
    </div>
@endsection
