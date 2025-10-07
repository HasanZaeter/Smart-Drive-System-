<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use App\Repositories\PermissionRepository;
use Illuminate\Auth\Access\Response;

class FolderPolicy
{
    protected $permissionRepo;

    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepo = $permissionRepository;
    }
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
    public function view(User $user, Folder $folder): bool
    {
        return $user->id === $folder->user_id || $this->permissionRepo->userHasPermission($user, $folder, 1);
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
    public function update(User $user, Folder $folder): bool
    {
        return $user->id === $folder->user_id || $this->permissionRepo->userHasPermission($user, $folder, 3);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Folder $folder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Folder $folder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Folder $folder): bool
    {
        return false;
    }
}
