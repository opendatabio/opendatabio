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
@lang('messages.hint_taxon_create')
      </div>
    </div>
  </div>
            <div class="panel panel-default">
                <div class="panel-heading">
		@lang('messages.new_taxon')
<div style="float:right;">
<a href="http://tropicos.org/"><img src="{{asset('images/TropicosLogo.gif')}}" alt="Tropicos"></a>
<a href="http://www.ipni.org/"><img src="{{asset('images/IpniLogo.png')}}" alt="IPNI" width="33px"></a>
<a href="http://www.mycobank.org/"><img src="{{asset('images/MBLogo.png')}}" alt="Mycobank" width="33px"></a>
                </div>
                </div>

                <div class="panel-body">
                    <!-- Display Validation Errors -->
		    @include('common.errors')

<div id="ajax-error" class="collapse alert alert-danger">
@lang('messages.whoops')
</div>
@if (isset($taxon))
		    <form action="{{ url('taxons/' . $taxon->id)}}" method="POST" class="form-horizontal">
{{ method_field('PUT') }}

@else
		    <form action="{{ url('taxons')}}" method="POST" class="form-horizontal">
@endif
		    <input type="hidden" name="route-url" value="{{ route('checkapis') }}">
		     {{ csrf_field() }}
<!-- name -->
<div class="form-group">
    <label for="name" class="col-sm-3 control-label mandatory">
@lang('messages.name')
</label>
        <a data-toggle="collapse" href="#hint1" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="name" id="name" class="form-control" value="{{ old('name', isset($taxon) ? $taxon->fullname : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint1" class="panel-collapse collapse">
	@lang ('messages.hint_taxon_name')
    </div>
  </div>
</div>

		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-primary" id="checkapis">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.checkapis')
				</button>
				<div class="spinner" id="spinner"> </div>
			    </div>
			</div>

<!-- tax rank -->
<div class="form-group">
    <label for="level" class="col-sm-3 control-label mandatory">
@lang('messages.taxon_level')
</label>
        <a data-toggle="collapse" href="#hint2" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<?php $selected = old('level', isset($taxon) ? $taxon->level : null); ?>

	<select name="level" id="level" class="form-control" >
	@foreach (App\Taxon::TaxLevels() as $level)
		<option value="{{$level}}" {{ $level == $selected ? 'selected' : '' }}>
			@lang ('levels.tax.' . $level )
		</option>
	@endforeach
	</select>
            </div>
  <div class="col-sm-12">
    <div id="hint2" class="panel-collapse collapse">
	@lang('messages.taxon_level_hint')
    </div>
  </div>
</div>

<!-- parent -->
<div class="form-group">
    <label for="parent_id" class="col-sm-3 control-label">
@lang('messages.parent')
</label>
        <a data-toggle="collapse" href="#hint6" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="parent_autocomplete" id="parent_autocomplete" class="form-control autocomplete"
    value="{{ old('parent_autocomplete', (isset($taxon) and $taxon->parent) ? $taxon->parent->qualifiedFullname : null) }}">
    <input type="hidden" name="parent_id" id="parent_id"
    value="{{ old('parent_id', isset($taxon) ? $taxon->parent_id : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint6" class="panel-collapse collapse">
	@lang('messages.taxon_parent_hint')
    </div>
  </div>
</div>

<!-- senior & is valid -->
<div class="form-group">
    <div class="col-md-6 col-md-offset-3">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="valid" id="valid" 
{{ (old('valid', isset($taxon) ? $taxon->valid : "on" ) != "off") ? 'checked' : '' }}
>
@lang('messages.valid')?

            </label>
        </div>
    </div>
</div>
<div class="form-group" id="super-senior">
    <label for="senior_id" class="col-sm-3 control-label">
@lang('messages.senior')
</label>
        <a data-toggle="collapse" href="#hint4" class="btn btn-default">?</a>
	    <div class="col-sm-6">
    <input type="text" name="senior_autocomplete" id="senior_autocomplete" class="form-control autocomplete"
    value="{{ old('senior_autocomplete', (isset($taxon) and $taxon->senior) ? $taxon->senior->qualifiedFullname : null) }}">
    <input type="hidden" name="senior_id" id="senior_id"
    value="{{ old('senior_id', isset($taxon) ? $taxon->senior_id : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint4" class="panel-collapse collapse">
	@lang('messages.taxon_senior_hint')
    </div>
  </div>
</div>

<!-- tax author -->
<div class="form-group">
    <label for="author_id" class="col-sm-3 control-label mandatory">
@lang('messages.taxon_author')
</label>
        <a data-toggle="collapse" href="#hint3" class="btn btn-default">?</a>
	    <div class="col-sm-6 group-together">
	<input type="text" name="author" id="author" class="form-control" value="{{ old('author', isset($taxon) ? $taxon->author : null) }}">
<div style="text-align:center;">- or -</div>
    <input type="text" name="author_autocomplete" id="author_autocomplete" class="form-control autocomplete"
    value="{{ old('author_autocomplete', (isset($taxon) and $taxon->author_person) ? $taxon->author_person->full_name . " [" . $taxon->author_person->abbreviation . "]" : null) }}">
    <input type="hidden" name="author_id" id="author_id"
    value="{{ old('author_id', isset($taxon) ? $taxon->author_id : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint3" class="panel-collapse collapse">
	@lang('messages.taxon_author_hint')
    </div>
  </div>
</div>

<!-- tax reference -->
<div class="form-group">
    <label for="bibreference_id" class="col-sm-3 control-label mandatory">
@lang('messages.taxon_bibreference')
</label>
        <a data-toggle="collapse" href="#hint5" class="btn btn-default">?</a>
	    <div class="col-sm-6 group-together">
	<input type="text" name="bibreference" id="bibreference" class="form-control" value="{{ old('bibreference', isset($taxon) ? $taxon->bibreference : null) }}">
<div style="text-align:center;">- or -</div>
    <input type="text" name="bibreference_autocomplete" id="bibreference_autocomplete" class="form-control autocomplete"
    value="{{ old('bibreference_autocomplete', (isset($taxon) and $taxon->reference) ? $taxon->reference->bibkey : null) }}">
    <input type="hidden" name="bibreference_id" id="bibreference_id"
    value="{{ old('bibreference_id', isset($taxon) ? $taxon->bibreference_id : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint5" class="panel-collapse collapse">
	@lang('messages.taxon_bibreference_hint')
    </div>
  </div>
</div>

<!-- External refs -->
<div class="form-group">
    <label for="mobotkey" class="col-sm-3 control-label">
@lang('messages.mobot_key')
</label>
        <a data-toggle="collapse" href="#hint7" class="btn btn-default">?</a>
	    <div class="col-sm-6">
	<input type="text" name="mobotkey" id="mobotkey" class="form-control" value="{{ old('mobotkey', isset($taxon) ? $taxon->mobot : null) }}">
            </div>
</div>
<div class="form-group">
    <label for="ipnikey" class="col-sm-3 control-label">
@lang('messages.ipni_key')
</label>
	    <div class="col-sm-6">
	<input type="text" name="ipnikey" id="ipnikey" class="form-control" value="{{ old('ipnikey', isset($taxon) ? $taxon->ipni : null) }}">
            </div>
</div>
<div class="form-group">
    <label for="mycobankkey" class="col-sm-3 control-label">
@lang('messages.mycobank_key')
</label>
	    <div class="col-sm-6">
	<input type="text" name="mycobankkey" id="mycobankkey" class="form-control" value="{{ old('mycobankkey', isset($taxon) ? $taxon->mycobank : null) }}">
            </div>
  <div class="col-sm-12">
    <div id="hint7" class="panel-collapse collapse">
	@lang ('messages.hint_mobot_key')
    </div>
  </div>
</div>

<!-- notes --> 
<div class="form-group">
    <label for="notes" class="col-sm-3 control-label">
@lang('messages.notes')
</label>
	    <div class="col-sm-6">
	<textarea name="notes" id="notes" class="form-control">{{ old('notes', isset($taxon) ? $taxon->notes : null) }}</textarea>
            </div>
</div>
		        <div class="form-group">
			    <div class="col-sm-offset-3 col-sm-6">
				<button type="submit" class="btn btn-success" name="submit" value="submit">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.add')

				</button>
				<a href="{{url()->previous()}}" class="btn btn-warning">
				    <i class="fa fa-btn fa-plus"></i>
@lang('messages.back')
				</a>
			    </div>
			</div>
		    </form>
        </div>
    </div>
@endsection

@push ('scripts')
<script>
$(document).ready(function() {
    $("#parent_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}","#parent_id", "@lang('messages.noresults')");
    $("#senior_autocomplete").odbAutocomplete("{{url('taxons/autocomplete')}}","#senior_id", "@lang('messages.noresults')");
    $("#author_autocomplete").odbAutocomplete("{{url('persons/autocomplete')}}","#author_id", "@lang('messages.noresults')");
    $("#bibreference_autocomplete").odbAutocomplete("{{url('references/autocomplete')}}","#bibreference_id", "@lang('messages.noresults')");
});
function setFields(vel) {
    var valid = $('#valid').is(":checked");
    switch (valid) {
    case false: 
        $('#super-senior').show(vel);
        break;
    case true: 
        $('#super-senior').hide(vel);
        break;
    }
}
$("#valid").change(function() { setFields(400); });
// trigger this on page load
setFields(0);

/** Ajax handling for registering taxons */
$("#checkapis").click(function(e) {
    $( "#spinner" ).css('display', 'inline-block');
    $.ajaxSetup({ // sends the cross-forgery token!
    headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
    })
        e.preventDefault(); // does not allow the form to submit
    $.ajax({
    type: "POST",
        url: $('input[name="route-url"]').val(),
        dataType: 'json',
        data: {'name': $('input[name="name"]').val()},
        success: function (data) {
            $( "#spinner" ).hide();
            if ("error" in data) {
                $( "#ajax-error" ).collapse("show");
                $( "#ajax-error" ).text(data.error);
            } else {
                if ($.isEmptyObject(data.bag)) {
                    // ONLY removes the error div if request is success and no messages
                    $( "#ajax-error" ).collapse("hide");
                } else {
                    $( "#ajax-error" ).collapse("show");
                    $( "#ajax-error" ).empty();
                    var newul = $(document.createElement( "ul" ));
                    $.each( data.bag, function( key, val ) {
                        var newli = $(document.createElement( "li" ));
                        newul.append( newli );
                        newli.text(val);
                    });
                    $( "#ajax-error" ).append(newul);
                }
                $("#level").val(data.apidata[0]);
                $("#author").val(data.apidata[1]);
                if (data.apidata[2]) {
                    $("#valid").prop('checked', true);
                } else {
                    $("#valid").prop('checked', false);
                }
                $("#bibreference").val(data.apidata[3]);
                if (data.apidata[4]) {
                    $("#parent_id").val(data.apidata[4][0]);
                    $("#parent_autocomplete").val(data.apidata[4][1]);
                } else {
                    $("#parent_id").val("");
                    $("#parent_autocomplete").val("");
                }
                if (data.apidata[5]) {
                    $("#senior_id").val(data.apidata[5][0]);
                    $("#senior_autocomplete").val(data.apidata[5][1]);
                } else {
                    $("#senior_id").val("");
                    $("#senior_autocomplete").val("");
                }
                $("#mobotkey").val(data.apidata[6]);
                $("#ipnikey").val(data.apidata[7]);
                $("#mycobankkey").val(data.apidata[8]);
                // finally, set fields visibility
                setFields(0);
            }
        },
        error: function(e){ 
            $( "#spinner" ).hide();
            $( "#ajax-error" ).collapse("show");
            $( "#ajax-error" ).text('Error sending AJAX request');
        }
        })
});
</script>
@endpush
