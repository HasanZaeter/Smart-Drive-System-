<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Http\Requests\CreateFileRequest;
use App\Http\Requests\SearchFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Repositories\PermissionRepository;
use App\Services\FileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    protected $fileService;
    protected $permissionRepo;
    public function __construct(FileService $fileService, PermissionRepository $permissionRepository)
    {
        $this->permissionRepo = $permissionRepository;
        $this->fileService = $fileService;
    }

    public function create(CreateFileRequest $request)
    {
        $validated = $request->validated();

        $created = $this->fileService->createNewFile($validated);

        if ($created) {
            return JsonResponseHelper::successResponse('upload file successfully', $created, 201);
        }
        return JsonResponseHelper::errorResponse("you can't upload file in other user folder's", [], 403);
    }

    public function update(UpdateFileRequest $request, File $file)
    {
        $validated = $request->validated();

        $data = $this->fileService->update($file, $validated);

        if ($data) {
            return JsonResponseHelper::successResponse('update file successfully', [], 200);
        }
        return JsonResponseHelper::errorResponse("you can't move file in other user folder's", [], 403);
    }

    public function  show(File $file)
    {
        $authUserId = Auth::user()->id;
        $folderId = $file->folder_id;
        $ownerFile = $file->user_id;
        $hasPermission = $this->permissionRepo->userHasPermission($authUserId, $folderId, 1);

        if ($ownerFile === Auth::user()->id || $hasPermission) {
            if (!Storage::disk('local')->exists('files/' . $file->name)) {
                return JsonResponseHelper::errorResponse('File not found', [], 404);
            }
            return response()->file(storage_path('app/private/files/' . $file->name));
        }

        return JsonResponseHelper::errorResponse("you can't show other user file's", [], 403);
    }

    public function delete(File $file)
    {
        $ownerFile = $file->user_id;
        if ($ownerFile === Auth::user()->id) {
            $this->fileService->delete($file);

            return JsonResponseHelper::successResponse('delete file successfully', [], 200);
        }
        return JsonResponseHelper::errorResponse("you haven't authorization to delete this file", [], 403);
    }

    public function search(SearchFileRequest $request)
    {
        $data = $this->fileService->search($request->validated());
        
        return JsonResponseHelper::successResponse('retreive all files', FileResource::collection($data), 200);
    }
}
