<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\User;
use App\ODBTrait;
use App\Dataset;


class TraitPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the odbtrait.
     */
    public function view(User $user, ODBTrait $odbtrait)
    {
        return true;
    }

    /**
     * Determine whether the user can create odbtraits.
     */
    public function create(User $user)
    {
        return $user->access_level >= User::USER;
    }

    /**
     * Determine whether the user can update a odbtrait.
     */
    public function update(User $user, ODBTrait $odbtrait)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        //persons that measured this trait
        $trait_used_by = array_unique($odbtrait->measurements()->get()->map(function($measurement) { return $measurement->person_id;})->toArray());
        //condition 1. user is the only measurer of trait
        $only_user = in_array($user->person_id,$trait_used_by) and 1 == count($trait_used_by);

        //persons that are admins to datasets that use trait
        $trait_used_datasets = array_unique($odbtrait->measurements()->get()->map(function($measurement) { return $measurement->dataset_id;})->toArray());
        $trait_datasets_admins = Dataset::whereIn('id',$trait_used_datasets)->get()->map(function($dataset) { return $dataset->admins()->pluck('person_id');})->toArray();
        $person_id=$user->person_id;
        $dataset_count = array_filter($trait_datasets_admins,function($admins) use($person_id){ return in_array($person_id,$admins);});
        //condition 2 user is admin to all datasets using trait
        $is_admin_datasets = count($dataset_count)==count($trait_datasets_admins);

        if (User::USER == $user->access_level and (0 == $odbtrait->measurements()->count() or $only_user or $is_admin_datasets)) {
            return true;
        }

        return false;
    }


    /**
     * Determine whether the user can delete the trait.
     */
    public function delete(User $user, ODBTrait $odbtrait)
    {
        return false;
    }
}
