<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Http\Requests\CreateFolderRequest;
use App\Http\Requests\SearchFolderRequest;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Repositories\PermissionRepository;
use App\Services\FolderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    use AuthorizesRequests;
    protected $folderService;
    protected $permissionRepo;
    public function __construct(FolderService $folderService, PermissionRepository $permissionRepository)
    {
        $this->permissionRepo = $permissionRepository;
        $this->folderService = $folderService;
    }

    public function foldersInRoot()
    {
        $data = $this->folderService->foldersInRoot();

        return JsonResponseHelper::successResponse('get folders and files succcessfully', $data);
    }

    public function create(CreateFolderRequest $request)
    {
        $validated = $request->validated();

        $created = $this->folderService->createNewFolder($validated);
        if ($created) {
            return JsonResponseHelper::successResponse('folder created successfully', [], 201);
        }
        return JsonResponseHelper::errorResponse("you can't add folder in other user folder's", [], 403);
    }

    public function show(Folder $folder)
    {
        $authUserId = Auth::user()->id;
        $ownerFolder = $folder->user_id;
        $hasPermission = $this->permissionRepo->userHasPermission($authUserId, $folder->id, 1);
        if ($ownerFolder === $authUserId || $hasPermission) {
            $data = $this->folderService->findItemsForFolder($folder);

            return JsonResponseHelper::successResponse('get files and folders successfully', $data, 200);
        }
        return JsonResponseHelper::errorResponse("you can't show other user folder's", [], 403);
    }

    public function update(Folder $folder, UpdateFolderRequest $request)
    {
        $validated = $request->validated();

        $data = $this->folderService->updateFolderInfo($folder, $validated);

        if ($data) {
            return JsonResponseHelper::successResponse('updated successfully', [], 200);
        }
        return JsonResponseHelper::errorResponse('you can move outer file to inner file', [], 403);
    }

    public function delete(Folder $folder)
    {
        $ownerFolder = $folder->user_id;
        if ($ownerFolder === Auth::user()->id) {
            $this->folderService->deleteFolder($folder);

            return JsonResponseHelper::successResponse('delete folder items successfully', [], 200);
        }

        return JsonResponseHelper::errorResponse("you haven't authorization to delete this folder", [], 403);
    }

    public function download(Folder $folder)
    {
        $authUserId = Auth::user()->id;
        $ownerFolder = $folder->user_id;
        $hasPermission = $this->permissionRepo->userHasPermission($authUserId, $folder->id, 1);
        if ($ownerFolder === $authUserId || $hasPermission) {
            $response = $this->folderService->downloadZipFolder($folder);

            return $response;
            // return JsonResponseHelper::successResponse('downloaded folder successfully', [], 200);
        }
    }

    public function search(SearchFolderRequest $request)
    {
        $validated = $request->validated();

        $data = $this->folderService->search($validated);

        return JsonResponseHelper::successResponse('retreive all folders', FolderResource::collection($data), 200);
    }
}
