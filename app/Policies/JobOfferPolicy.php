<?php

namespace App\Policies;

use App\Models\JobOffer;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;


class JobOfferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */

    public function view(User $user, JobOffer $jobOffer)
    {
        return $user->id === $jobOffer->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobOffer $jobOffer)
    {
        return $user->id === $jobOffer->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JobOffer $jobOffer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobOffer $jobOffer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, JobOffer $jobOffer): bool
    {
        return false;
    }
}
