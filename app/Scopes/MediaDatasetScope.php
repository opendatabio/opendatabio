<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\User;
use App\Models\Dataset;
use Auth;

class MediaDatasetScope implements Scope
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
            return $builder->whereRaw('((media.dataset_id IS NULL) OR media.dataset_id IN (SELECT id FROM datasets WHERE datasets.privacy >='.Dataset::PRIVACY_PUBLIC.'))');
        }
        // superadmins see everything
        if (User::ADMIN == Auth::user()->access_level) {
            return $builder;
        }

        // now the complex case: the regular user
        return $builder->whereRaw('((media.dataset_id IS NULL) OR media.id IN (SELECT media.id FROM media JOIN datasets ON datasets.id=media.dataset_id JOIN dataset_user ON dataset_user.dataset_id=datasets.id WHERE (datasets.privacy >='.Dataset::PRIVACY_REGISTERED.') OR dataset_user.user_id='.Auth::user()->id.'))');
    }
}
