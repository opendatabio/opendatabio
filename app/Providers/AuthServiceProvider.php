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
        \App\Models\Biocollection::class => \App\Policies\BiocollectionPolicy::class,
        \App\Models\Person::class => \App\Policies\PersonPolicy::class,
        \App\Models\BibReference::class => \App\Policies\BibReferencePolicy::class,
        \App\Models\Location::class => \App\Policies\LocationPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\UserJob::class => \App\Policies\UserJobPolicy::class,
        \App\Models\Taxon::class => \App\Policies\TaxonPolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\Dataset::class => \App\Policies\DatasetPolicy::class,
        \App\Models\Voucher::class => \App\Policies\VoucherPolicy::class,
        \App\Models\Individual::class => \App\Policies\IndividualPolicy::class,
        \App\Models\Tag::class => \App\Policies\TagPolicy::class,
        \App\Models\ODBTrait::class => \App\Policies\TraitPolicy::class,
        \App\Models\Measurement::class => \App\Policies\MeasurementPolicy::class,
        \App\Models\Picture::class => \App\Policies\PicturePolicy::class,
        \App\Models\Form::class => \App\Policies\FormPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
