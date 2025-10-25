<?php

namespace App\Repositories;

use App\Helpers\JsonResponseHelper;
use App\Models\Permission;
use App\Models\UserPermission;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionRepository
{
    public function __construct() {}

    public function create(array $data)
    {
        $insertData = [
            [
                'user_id' => $data['user_id'],
                'permission_id' => $data['permission_id'],
                'folder_id' => $data['folder_id'],
                'created_at' => now(),
            ]
        ];

        $folderIds = DB::table('folders')
            ->where(function ($query) use ($data) {
                $query->where('parents_list', 'like', '%/' . $data['folder_id'] . '/%')
                    ->orWhere('parents_list', 'like', $data['folder_id'] . '/%');
            })
            ->pluck('id')
            ->toArray();

        $childernData = array_map(fn($folderId) => [
            'user_id' => $data['user_id'],
            'permission_id'  => $data['permission_id'],
            'folder_id' => $folderId,
            'created_at' => now(),
        ], $folderIds);

        $insertData = array_merge($insertData, $childernData);
        
        UserPermission::insertOrIgnore($insertData);
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

    public function deleteAll($childernsIds)
    {
        DB::table('user_permissions')
            ->whereIn('folder_id', $childernsIds)
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

    public function getPermissionForFolder($folderId)
    {
        return UserPermission::where('folder_id', $folderId)->select(['user_id', 'permission_id'])->get();
    }
}
