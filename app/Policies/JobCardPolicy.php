<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JobCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobCardPolicy
{
    use HandlesAuthorization;

    private function hasPermission(User $user, string $permission): bool
    {
        $rolePermissions = config('permissions.role_permissions', []);
        $userPermissions = $rolePermissions[$user->role] ?? [];

        return in_array('*', $userPermissions, true)
            || in_array($permission, $userPermissions, true);
    }

    private function sameTenant(User $user, JobCard $jobCard): bool
    {
        return (string) $user->tenant_id === (string) $jobCard->tenant_id;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-job-cards');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JobCard $jobCard): bool
    {
        return $this->sameTenant($user, $jobCard)
            && $this->hasPermission($user, 'view-job-cards');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create-job-cards');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobCard $jobCard): bool
    {
        return $this->sameTenant($user, $jobCard)
            && $this->hasPermission($user, 'edit-job-cards');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JobCard $jobCard): bool
    {
        return $this->sameTenant($user, $jobCard)
            && $this->hasPermission($user, 'delete-job-cards');
    }
}
