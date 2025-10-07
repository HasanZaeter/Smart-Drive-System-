<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Permission;
use App\Models\UserPermission;
use App\Repositories\FolderRepository;
use App\Repositories\PermissionRepository;

class PermissionService
{
    protected $permissionRepo;
    protected $folderRepo;
    public function __construct(PermissionRepository $permissionRepository, FolderRepository $folderRepository)
    {
        $this->folderRepo = $folderRepository;
        $this->permissionRepo = $permissionRepository;
    }

    public function assignPermissionToUser($data)
    {
        $this->permissionRepo->create($data);
    }

    public function updatePermissions($data, UserPermission $userPermission)
    {
        $this->permissionRepo->update($userPermission, $data);
    }

    public function revokePermissionsFromUser(Folder $folder, Permission $permission)
    {
        $childernsIds = $this->folderRepo->findChildrensIdsByParentFolder($folder);
        $childernsIds[] = $folder->id;

        $this->permissionRepo->delete($childernsIds, $permission);
    }
}
