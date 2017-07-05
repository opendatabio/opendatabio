<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Herbarium::class => \App\Policies\HerbariumPolicy::class,
        \App\Person::class    => \App\Policies\PersonPolicy::class,
        \App\BibReference::class    => \App\Policies\BibReferencePolicy::class,
        \App\Location::class    => \App\Policies\LocationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
