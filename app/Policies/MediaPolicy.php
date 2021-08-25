<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Policies;

use App\Models\User;
use App\Models\Media;
use App\Models\Dataset;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can view the media.
     */
    public function view(User $user, Media $media)
    {
        // is handled by App\Models\Media::boot globalscope
        return true;

    }

    /**
     * Determine whether the user can create media files under a given project if given
     */
    public function create(User $user, Dataset $dataset = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // project is not mandatory for media
        // full users can create
        if (is_null($dataset)) {
            return User::USER == $user->access_level;
        }
        // for regular users, when actually creating a voucher
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user));
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user));
    }

    /**
     * Determine whether the user can update a media record
     * (not the media file, this cannot be update).
     */
    public function update(User $user, Media $media, Dataset $dataset = null)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // who uploaded the media has access
        $user_id = $media->getCustomProperty('user_id');

        if (is_null($dataset)) {
          $dataset = $media->dataset;
        }
        if (is_null($dataset)) {
          // only regular users, project people or if the user created the media
          return User::USER == $user->access_level and $user->id == $user_id;
        }
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user) or $user->id == $user_id);
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user) or $user->id == $user_id);
    }

    /**
     * Determine whether the user can delete a media record.
     */
    public function delete(User $user,  Media $media)
    {
        if (User::ADMIN == $user->access_level) {
            return true;
        }
        // who uploaded the media has access
        $user_id = $media->getCustomProperty('user_id');
        if (null == $media->dataset_id) {
          // only regular users, project people or if the user created the media
          return User::USER == $user->access_level and $user->id == $user_id;
        }
        // for regular users
        $dataset = $media->dataset;
        $privacy = $dataset->privacy;
        if ($privacy==Dataset::PRIVACY_PROJECT) {
          return User::USER == $user->access_level and
              ($dataset->project->admins->contains($user) or $dataset->project->collabs->contains($user) or $user->id == $user_id);
        }
        return User::USER == $user->access_level and ($dataset->admins->contains($user) or $dataset->collabs->contains($user) or $user->id == $user_id);
    }

}
