<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Required for compatibility with MySQL < 5.7
        // see https://github.com/laravel/docs/blob/5.4/migrations.md
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->when(MigrationCreator::class)->needs('$customStubPath')->give(function ($app) {
              return $app->basePath('stubs');
        });
    }



}
