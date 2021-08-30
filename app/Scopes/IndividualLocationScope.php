<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\User;
use App\Models\Dataset;
use Auth;

class IndividualLocationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
      // first, the easy cases. No logged in user? can access only public (privacy 2)
      if (is_null(Auth::user())) {
          return $builder->whereRaw('(individual_location.individual_id IN (SELECT inds.id FROM individuals as inds WHERE inds.dataset_id IS NULL) OR individual_location.individual_id IN (SELECT indvs.id FROM individuals as indvs JOIN datasets as dts ON dts.id=indvs.dataset_id WHERE dts.privacy >='.Dataset::PRIVACY_PUBLIC.'))');
      }
      // superadmins see everything
      if (User::ADMIN == Auth::user()->access_level) {
          return $builder;
      }
      // now the complex case: the regular user see any registered or public or those having specific authorization
      return $builder->whereRaw('(individual_location.individual_id IN (SELECT inds.id FROM individuals as inds WHERE inds.dataset_id IS NULL) OR individual_location.individual_id IN (SELECT indvs.id FROM individuals as indvs JOIN datasets as dts ON dts.id=indvs.dataset_id JOIN dataset_user as dtu ON dtu.dataset_id=dts.id WHERE (dts.privacy >='.Dataset::PRIVACY_REGISTERED.') OR (dtu.user_id='.Auth::user()->id.')))');
    }
}
