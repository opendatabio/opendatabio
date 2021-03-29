@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.measurement')
                    <span class="history" style="float:right">
                    <a href="{{url("measurements/$measurement->id/activity")}}">
                    @lang ('messages.see_history')
                    </a>
                    </span>
		<div class="panel-body">
<p><strong>
@lang('messages.object')
: </strong>
{!! $measurement->measured->rawLink(true) !!}

<p><strong>
@lang('messages.trait')
: </strong>
{!! $measurement->odbtrait->rawLink() !!}
<br><em>
{{$measurement->odbtrait->description}}</em>
</p>
<p><strong>
@lang('messages.value')
: </strong>
{{$measurement->valueDisplay}} {{ $measurement->odbtrait->unit }}
@if ($measurement->type == \App\Models\ODBTrait::COLOR)
&nbsp;<span class="measurement-thumb" style="background-color: {{$measurement->valueActual}}">
@endif
</p>


<p><strong>
@lang('messages.dataset')
:</strong>
{!! $measurement->dataset->rawLink() !!}
</p>

@if ($measurement->notes)
<p><strong>
@lang('messages.notes')
:</strong>
{{$measurement->notes}}</a>
</p>
@endif

<p><strong>
@lang('messages.measurement_date')
:</strong>
{{$measurement->formatDate}}
</p>

@if ($measurement->person)
<p><strong>
@lang('messages.measurement_measurer')
:</strong>
{!! $measurement->person->rawLink() !!}
</p>
@endif

@if ($measurement->bibreference)
<p><strong>
@lang('messages.reference')
:</strong>
{!! $measurement->bibreference->rawLink() !!}
</p>
@endif

@can ('update', $measurement)
			    <div class="col-sm-6">
				<a href="{{ url('measurements/'. $measurement->id. '/edit')  }}" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.edit')

				</a>
			    </div>
@endcan
                </div>


            </div>
<!-- Other details (specialist, biocollection, collects, etc?) -->
    </div>


    @if (isset($chartjs))
    <div class="panel panel-default">
      <div class="panel-body" >
        @if (isset($chartjs))
          <div style = "width:100%; height:400px; overflow:auto;">
            {!! $chartjs->render() !!}
          </div>
        @endif
      </div>
    </div>
    @endif

@endsection
