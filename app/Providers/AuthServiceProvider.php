<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Biocollection::class => \App\Policies\BiocollectionPolicy::class,
        \App\Person::class => \App\Policies\PersonPolicy::class,
        \App\BibReference::class => \App\Policies\BibReferencePolicy::class,
        \App\Location::class => \App\Policies\LocationPolicy::class,
        \App\User::class => \App\Policies\UserPolicy::class,
        \App\UserJob::class => \App\Policies\UserJobPolicy::class,
        \App\Taxon::class => \App\Policies\TaxonPolicy::class,
        \App\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Dataset::class => \App\Policies\DatasetPolicy::class,
        \App\Voucher::class => \App\Policies\VoucherPolicy::class,
        \App\Individual::class => \App\Policies\IndividualPolicy::class,
        \App\Tag::class => \App\Policies\TagPolicy::class,
        \App\ODBTrait::class => \App\Policies\TraitPolicy::class,
        \App\Measurement::class => \App\Policies\MeasurementPolicy::class,
        \App\Picture::class => \App\Policies\PicturePolicy::class,
        \App\Form::class => \App\Policies\FormPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
