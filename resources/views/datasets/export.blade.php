@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    @lang('messages.dataset')
                </div>
		<div class="panel-body">
		    <p><strong>
@lang('messages.name')
: </strong>  {{ $dataset->name }} </p>
<p><strong>
@lang('messages.privacy')
:</strong>
@lang ('levels.privacy.' . $dataset->privacy)
</p>
<p><strong>
@lang('messages.admins')
:</strong>
<ul>
@foreach ($dataset->users()->wherePivot('access_level', '=', App\Project::ADMIN)->get() as $admin)
  @php
  if ($admin->person->fullname) {
    $adm = $admin->person->fullname." ".$admin->email;
  } else {
    $adm = $admin->email;
  }
  @endphp
  <li> {{ $adm }} </li>
@endforeach
</ul>
</p>
@if ($dataset->notes)
<p><strong>
@lang('messages.notes')
: </strong> {{$dataset->notes}}
</p>
@endif
<hr>
@if ($dataset->policy)
<p><strong>
@lang('messages.dataset_policy')
: </strong> {{$dataset->policy}}
</p>
@endif

@if($dataset->references->where('mandatory',1)->count())
  <p>
<strong>
  @lang('messages.dataset_bibreferences_mandatory')
</strong>:
<ul>
  @foreach($dataset->references->where('mandatory',1) as $reference)
    <li>
    <a href='{{ url('references/'.$reference->bib_reference_id)}}'>
      {!!$reference->first_author.". ".$reference->year !!}
    </a>
    {!! $reference->title !!}
  </li>
  @endforeach
</ul>
</p>
@endif


</div>
</div>


<!--- DATASET REQUEST PANEL -->
@if(Auth::user())
<div class="panel panel-default" id='dataset_request_panel' >
  <div class="panel-heading">
    @lang('messages.dataset_request')
    &nbsp;&nbsp;<a data-toggle="collapse" href="#hint_dataset_resquest" class="btn btn-default">?</a>
    <div id="hint_dataset_resquest" class="panel-collapse collapse">
      <div class="panel-body">
@lang('messages.dataset_request_hint')
      </div>
  </div>
  </div>
  <div class="panel-body">

  <form action="{{ url('datasets/'.$dataset->id.'/emailrequest')}}" method="POST" class="form-horizontal" id='dataset_request_form'>
  <!-- csrf protection -->
    {{ csrf_field() }}
    <!-- hidden field to store selected rows not really in use now as single datasets only are implemented-->
    <input type='hidden' name='dataset_list' id='dataset_list' value='' value="">


    <div class="form-group">
      <label class="col-sm-3 control-label mandatory">
        @lang('messages.dataset_request_use')
      </label>
      <div class="col-sm-7">
          <input type="radio" name="dataset_use_type" value="@lang('messages.dataset_request_use_exploratory')">&nbsp;@lang('messages.dataset_request_use_exploratory')
          <input type="radio" name="dataset_use_type" value="@lang('messages.dataset_request_use_inpublications')" >&nbsp;@lang('messages.dataset_request_use_inpublications')
      </div>
     </div>
    <div class="form-group">
      <label class="col-sm-3 control-label mandatory">
        @lang('messages.description')
      </label>
      <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
      <div class="col-sm-7">
        <textarea name="dataset_use_description" ></textarea>
      </div>
      <div class="col-sm-12">
        <div id="hint6" class="panel-collapse collapse">
	         @lang('messages.dataset_request_use_description_hint')
         </div>
       </div>
     </div>
     <div class="form-group">
       <label class="col-sm-3 control-label mandatory">
         @lang('messages.dataset_request_agreement')
       </label>
       <div class="col-sm-7">
         @lang('messages.dataset_request_agreement_text')
         <br><br>
         <input type="checkbox" name="dataset_agreement[]"  value="@lang('messages.dataset_request_distribution_agreement')">&nbsp;@lang('messages.dataset_request_distribution_agreement')
         <br>
         @if($dataset->policy)
         <input type="checkbox" name="dataset_agreement[]"  value="@lang('messages.dataset_request_policy_agreement')" >&nbsp;@lang('messages.dataset_request_policy_agreement')
          @endif
          @if($dataset->references->where('mandatory',1)->count())
          <input type="checkbox" name="dataset_agreement[]"  value="@lang('messages.dataset_request_citation_agreement')" >&nbsp;@lang('messages.dataset_request_citation_agreement')
          @endif
       </div>
      </div>
      <div class="form-group">
			<div class="col-sm-offset-3 col-sm-6">
        <span id='submitting' hidden><i class="fas fa-sync fa-spin"></i></span>
        <input id='submit' class="btn btn-success" name="submit" type='submit' value='@lang('messages.submit')' >
        </div>
			</div>
</form>

</div>
</div>
<!-- END DATASET REQUEST FORM -->
@else
  <!--- user is not logged and so cannot make a request -->
  <div class="panel panel-default" id='dataset_request_panel' >
    <div class="panel-body">
      <p class='warning'>
          @lang('messages.dataset_request_nouser')
      </p>

    </div>
</div>

@endif



</div>
    </div>
@endsection
@push('scripts')

  <script>
  $(document).ready(function() {
    /* summarize project */
    $('#submit').on('click', function(e){
          $('#submitting').show();
        });
  });
 </script>
@endpush
