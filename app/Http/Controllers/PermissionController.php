<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Http\Requests\CreatePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\UserPermission;
use App\Repositories\FolderRepository;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    protected $permissionService;
    protected $folderRepo;
    public function __construct(PermissionService $permissionService, FolderRepository $folderRepository)
    {
        $this->folderRepo = $folderRepository;
        $this->permissionService = $permissionService;
    }

    public function create(CreatePermissionRequest $request, Folder $folder)
    {
        // return "hello world"    ;
        $validated = $request->validated();
        $validated['folder_id'] = $folder->id;

        $this->permissionService->assignPermissionToUser($validated);

        return JsonResponseHelper::successResponse('assign permission successfully', [], 201);
    }

    public function update(UpdatePermissionRequest $request, UserPermission $userPermission)
    {
        $validated = $request->validated();
        // $validated['folder_id'] = $folder->id;

        $this->permissionService->updatePermissions($validated, $userPermission);

        return JsonResponseHelper::successResponse('update permission successfully', [], 200);
    }

    public function delete(Folder $folder, Permission $permission)
    {
        // $folder = $userPermission->folder;
        $ownerFolder = $folder->user_id;
        if ($ownerFolder === Auth::user()->id) {
            $this->permissionService->revokePermissionsFromUser($folder,$permission);

            return JsonResponseHelper::successResponse('delete folder items successfully', [], 200);
        }

        return JsonResponseHelper::errorResponse("you can't revoke permission from other users'folder", [], 403);
    }
}
