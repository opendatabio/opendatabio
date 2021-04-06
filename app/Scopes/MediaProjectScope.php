<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\User;
use Auth;

class MediaProjectScope implements Scope
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
            return $builder->whereRaw('(media.project_id IS NULL) OR media.project_id IN (SELECT id FROM projects WHERE projects.privacy = 2)');
        }
        // superadmins see everything
        if (User::ADMIN == Auth::user()->access_level) {
            return $builder;
        }

        // now the complex case: the regular user see any registered or public or those having specific authorization
        return $builder->whereRaw('(media.project_id IS NULL)  OR media.project_id IN (SELECT id FROM projects WHERE projects.privacy = 2) OR media.id IN (SELECT media.id FROM media JOIN projects ON projects.id=media.project_id JOIN project_user ON project_user.project_id=projects.id WHERE projects.privacy>0 OR project_user.user_id='.Auth::user()->id.')');
    }
}
