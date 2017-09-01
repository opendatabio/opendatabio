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
        \App\User::class    => \App\Policies\UserPolicy::class,
        \App\UserJobs::class    => \App\Policies\UserJobsPolicy::class,
        \App\Taxon::class    => \App\Policies\TaxonPolicy::class,
        \App\Project::class    => \App\Policies\ProjectPolicy::class,
        \App\Plant::class    => \App\Policies\PlantPolicy::class,
        \App\Voucher::class    => \App\Policies\VoucherPolicy::class,
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
