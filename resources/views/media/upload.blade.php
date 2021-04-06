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
            @lang('messages.media_upload_hint')
          </div>
        </div>
      </div>

@can ('create', App\Models\Media::class)
  <div class="panel panel-default">
    <div class="panel-heading">
      @lang('messages.media_upload')
    </div>
    <div class="panel-body">
    <!-- Display Validation Errors -->
    @include('common.errors')
<form action="{{ url('import/media')}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
{{ csrf_field() }}
<div class="form-group">
  <label for="imageattributes" class="col-sm-4 control-label mandatory">
      @lang('messages.media_attribute_table')
  </label>
  <a data-toggle="collapse" href="#hint_img_attributes" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="file" name="media_attribute_table" id="media_attribute_table">
  </div>
  <div class="col-sm-12">
    <div id="hint_img_attributes" class="panel-collapse collapse">
      @lang('messages.media_attribute_table_hint',['validlicenses' => implode(' | ',config('app.creativecommons_licenses'))])
    </div>
  </div>
</div>

<div class="form-group">
  <label for="file" class="col-sm-4 control-label mandatory">
      @lang('messages.media_files')
  </label>
  <a data-toggle="collapse" href="#media_files_hint" class="btn btn-default">?</a>
  <div class="col-sm-6">
    <input type="file" name="file[]" id="mediaFiles" multiple class='filepond'>
  </div>
  <div class="col-sm-12">
    <div id="media_files_hint" class="panel-collapse collapse">
@lang('messages.media_files_hint')
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
            var oldvals = $('#media_paths').val().split(';');
            oldvals.push(response);
            $('#media_paths').val(oldvals.join(';'));
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
