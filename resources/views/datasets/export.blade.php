@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
          <div class="panel panel-default">
            <div class="panel-heading">
              @lang('messages.dataset')
              @if(isset($dataset))
                : <strong>{{$dataset->name}}</strong>
              @endif
            </div>
            @php
              $lockimage= '<i class="fas fa-lock"></i>';
              if ($dataset->privacy != App\Models\Dataset::PRIVACY_AUTH) {
                $license = explode(" ",$dataset->license);
                $license_logo = 'images/'.mb_strtolower($license[0]).".png";
              } else {
                $license_logo = 'images/cc_srr_primary.png';
              }
              if ($dataset->privacy == App\Models\Dataset::PRIVACY_PUBLIC) {
                $lockimage= '<i class="fas fa-lock-open"></i>';
              }
            @endphp
            <div class="panel-body">
              <h3>
                {{$dataset->title}}
              </h3>
              @if (isset($dataset->description))
              <p>
                {{ $dataset->description }}
              </p>
              @endif
              <br>
              <p>
                <a href="http://creativecommons.org/license" target="_blank">
                  <img src="{{ asset($license_logo) }}" alt="{{ $dataset->license }}" width=100>
                </a>
                {!! $lockimage !!} @lang('levels.privacy.'.$dataset->privacy)
              </p>

              @if (isset($dataset->citation))
                <br>
                <p>
                  <strong>@lang('messages.howtocite')</strong>:
                  <br>
                  {!! $dataset->citation !!}  <a data-toggle="collapse" href="#bibtex" class="btn-sm btn-primary">BibTeX</a>
                </p>
                <div id='bibtex' class='panel-collapse collapse'>
                  <pre><code>{{ $dataset->bibtex }}</code></pre>
                </div>
              @endif

              @if ($dataset->policy)
                <br>
                <p>
                  <strong>
                    @lang('messages.data_policy')
                  </strong>:
                  <br>
                  {{$dataset->policy}}
                </p>
                <br>
              @endif

              <p>
                <strong>
                  @lang('messages.admins')</strong>:
                  <ul>
                    @foreach ($dataset->users()->wherePivot('access_level', '=', App\Models\Project::ADMIN)->get() as $admin)
                      @php
                      if ($admin->person) {
                        $adm = $admin->person->fullname." ".$admin->email;
                      } else {
                        $adm = $admin->email;
                      }
                      @endphp
                      <li> {{ $adm }} </li>
                    @endforeach
                  </ul>
              </p>

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
<div class="panel panel-default" id='dataset_request_panel' >
  <div class="panel-heading">
    @lang('messages.data_request')
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

@if (!Auth::user())
  <div class="form-group">
    <label class="col-sm-3 control-label mandatory">
      @lang('messages.email')
    </label>
    <div class="col-sm-7">
      <input type="email" name="email" value="" >
    </div>
  </div>
@endif

    <div class="form-group">
      <label class="col-sm-3 control-label mandatory">
        @lang('messages.dataset_request_use')
      </label>
      <div class="col-sm-7">
          <input type="radio" name="dataset_use_type" value="@lang('messages.dataset_request_use_exploratory')" required >&nbsp;@lang('messages.dataset_request_use_exploratory')
          <input type="radio" name="dataset_use_type" value="@lang('messages.dataset_request_use_inpublications')" required >&nbsp;@lang('messages.dataset_request_use_inpublications')
      </div>
     </div>

    <div class="form-group">
      <label class="col-sm-3 control-label mandatory">
        @lang('messages.description')
      </label>
      <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
      <div class="col-sm-7">
        <textarea name="dataset_use_description" required></textarea>
      </div>
      <div class="col-sm-12">
        <div id="hint6" class="panel-collapse collapse">
	         @lang('messages.dataset_request_use_description_hint')
         </div>
       </div>
     </div>

     <!---
     <div class="form-group" hidden>
       <label class="col-sm-3 control-label mandatory">
         @lang('messages.dataset_request_agreement')
       </label>
       <div class="col-sm-7">
         @lang('messages.dataset_request_agreement_text')
         <br><br>
         <input type="checkbox" name="dataset_agreement[]"  value="@lang('messages.dataset_request_distribution_agreement')" required >&nbsp;@lang('messages.dataset_request_distribution_agreement')
         <br>
         @if($dataset->policy)
         <input type="checkbox" name="dataset_agreement[]"  value="@lang('messages.dataset_request_policy_agreement')" required >&nbsp;@lang('messages.dataset_request_policy_agreement')
          @endif
          <br>
          @if($dataset->references->where('mandatory',1)->count())
          <input type="checkbox" name="dataset_agreement[]"  value="@lang('messages.dataset_request_citation_agreement')" required >&nbsp;@lang('messages.dataset_request_citation_agreement')
          @endif
       </div>
      </div>
      -->
      <div class="form-group">
			<div class="col-sm-offset-3 col-sm-6">
        <span id='submitting' hidden><i class="fas fa-sync fa-spin"></i></span>
        <input id='submit' class="btn btn-success" name="submit" type='submit' value='@lang('messages.submit')' >
        </div>
			</div>
</form>

</div>
</div>
<!-- END DATASET REQUEST FORM




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
