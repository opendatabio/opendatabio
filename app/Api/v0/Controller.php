<?php

namespace App\Api\v0;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Response;
use URL;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function wrap_response($data) {
        return Response::json([
            'meta' => [
                'odb_version' => config('app.version'), 
                'api_version' => 'v0', 
                'server' => url('/'),
                'full_url' => URL::full(),
            ],
            'data' => $data,
        ]);
    }
}
