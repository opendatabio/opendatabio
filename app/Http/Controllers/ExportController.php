<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Response;
use Lang;
use App\UserJob;
use App\Jobs\ExportData;

class ExportController extends Controller
{

  /* this a a generic function to dispatch export jobs from datatables selections or full table*/
  /* exports are produced with the ExportData Job */
  public function exportData(Request $request)
  {

    UserJob::dispatch(ExportData::class,
    [
      'data' => [
        'data' => $request->all(),
      ]
    ]);
    return redirect()->back()->withStatus(Lang::get('messages.dispatched'));
  }


}
