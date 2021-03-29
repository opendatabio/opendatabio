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
	@lang('messages.pictures_upload_hint')
      </div>
    </div>
  </div>
@can ('create', App\Models\Picture::class)
  <div class="panel panel-default">
    <div class="panel-heading">
      @lang('messages.pictures_upload')
    </div>
    <div class="panel-body">
    <!-- Display Validation Errors -->
    @include('common.errors')
<form action="{{ url('importPictures')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
{{ csrf_field() }}
<div class="form-group">
  <label for="imageattributes" class="col-sm-4 control-label mandatory">
      @lang('messages.pictures_attribute_table')
  </label>
  <a data-toggle="collapse" href="#hint_img_attributes" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="file" name="pictures_attribute_table" id="pictures_attribute_table">
  </div>
  <div class="col-sm-12">
    <div id="hint_img_attributes" class="panel-collapse collapse">
@lang('messages.pictures_attribute_table_hint')
    </div>
  </div>
</div>

<div class="form-group">
  <label for="picture_files" class="col-sm-4 control-label mandatory">
      @lang('messages.pictures_files')
  </label>
  <a data-toggle="collapse" href="#pictures_files_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="file" name="file[]" id="pictures" multiple class='filepond'>
  </div>
  <div class="col-sm-12">
    <div id="pictures_files_hint" class="panel-collapse collapse">
@lang('messages.pictures_files_hint')
    </div>
  </div>
</div>
<div class="form-group">
<div class="col-sm-offset-3 col-sm-10">
    <button type="submit" class="btn btn-success" name="submit" value="submit">
    <i class="fa fa-btn fa-plus"></i>
    @lang('messages.add')
</button>
</div>
<div>
</form>

</div>
</div>
@endcan

</div>
</div>

@endsection
@push('scripts')
<script>

  //FilePond.registerPlugin(
  //        FilePondPluginImagePreview,
  //);

  FilePond.setOptions({
    server: {
     url: '../filepond/api',
     process: {
          url: '/process',
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          /*
          onload: (response) =>  {
            var oldvals = $('#pictures_paths').val().split(';');
            oldvals.push(response);
            $('#pictures_paths').val(oldvals.join(';'));
            //alert(response)
          }
          */
      },
      revert: '/process'
    }
  });
  const inputElement = document.querySelector('.filepond');
  const pond = FilePond.create( inputElement );
</script>
@endpush
