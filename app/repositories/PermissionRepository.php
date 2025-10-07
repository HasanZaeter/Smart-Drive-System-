<?php

namespace App\Repositories;

use App\Models\Folder;
use App\Models\Permission;
use App\Models\UserPermission;
use Illuminate\Support\Facades\DB;

class PermissionRepository
{
    public function __construct() {}

    public function create(array $data)
    {
        UserPermission::create($data);

        $folders = DB::table('folders')
            ->where('parents_list', 'like', '%' . $data['folder_id'] . '%')
            ->get();

        foreach ($folders as $folder) {
            UserPermission::create([
                'user_id' => $data['user_id'],
                'permission_id'  => $data['permission_id'],
                'folder_id' => $folder->id
            ]);
        }
    }

    public function update(UserPermission $userPermission, $data)
    {
        $userPermission->update($data);
    }

    public function delete($childernsIds, Permission $permission)
    {
        DB::table('user_permissions')
            ->whereIn('folder_id', $childernsIds)
            ->where('permission_id', $permission->id)
            ->delete();
    }

    public function userHasPermission($userId, $folderId, $permissionId)
    {
        return DB::table('user_permissions')
            ->where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('permission_id', $permissionId)
            ->exists();
    }
}
