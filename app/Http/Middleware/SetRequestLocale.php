<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Session;
use Config;
use Log;

class SetRequestLocale
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		if (Session::has('applocale') AND array_key_exists(Session::get('applocale'), Config::get('languages'))) {
			App::setLocale(Session::get('applocale'));
			Log::info ("MA QUE CARALHO SETTING ". Session::get('applocale'));
		}
		else { // This is optional as Laravel will automatically set the fallback language if there is none specified
			App::setLocale(Config::get('app.fallback_locale'));
		}
		return $next($request);
	}
}
