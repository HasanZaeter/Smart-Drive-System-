<?php

namespace App\Services;

use App\Helpers\JsonResponseHelper;
use App\Models\File;
use App\Models\Folder;
use App\Repositories\FolderRepository;
use App\Repositories\PermissionRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FolderService
{
    protected $folderRepo;
    protected $permissionRepo;
    public function __construct(FolderRepository $folderRepository, PermissionRepository $permissionRepository)
    {
        $this->permissionRepo = $permissionRepository;
        $this->folderRepo = $folderRepository;
    }

    public function foldersInRoot()
    {
        return $this->folderRepo->getRootItems();
    }

    public function createNewFolder($data)
    {
        try {
            if (!empty($data['parent_id'])) {
                return $this->createChildFolder($data);
            }

            return $this->createRootFolder($data);
        } catch (Exception $e) {
            return JsonResponseHelper::errorResponse($e, [], 500);
        }
    }

    protected function createChildFolder(array $data)
    {
        $userId = Auth::id();

        if (!$this->canCreateInFolder($userId, $data['parent_id'])) {
            return false;
        }

        $parentFolder = $this->folderRepo->findById($data['parent_id']);

        $data['name'] = $this->generateUniqueName($parentFolder->user_id, $data['name'], $data['parent_id']);

        $parentList = $parentFolder->parents_list ?? '';
        $data['parents_list'] = $parentList . $data['parent_id'] . '/';

        $data['user_id'] = $parentFolder->user_id;

        $newFolder = $this->folderRepo->create($data);

        if ($newFolder->user_id !== $userId) {
            $this->assignWriteAndReadPermission($newFolder->id, $userId);
        }

        return true;
    }
    protected function createRootFolder(array $data)
    {
        $userId = Auth::user()->id;
        $data['user_id'] = $userId;

        $data['name'] = $this->generateUniqueName($userId, $data['name'], $data['parent_id'] ?? null);

        $this->folderRepo->create($data);

        return true;
    }

    protected function generateUniqueName(int $userId, string $name, ?int $parentId = null): string
    {
        $count = $this->folderRepo->findByName($userId, $name, $parentId);

        return $count > 0
            ? $name . ' Copy (' . ($count + 1) . ')'
            : $name;
    }

    public function assignWriteAndReadPermission($folderId, $userId)
    {
        $this->permissionRepo->create([
            'user_id' => $userId,
            'folder_id' => $folderId,
            'permission_id' => 2
        ]);
        $this->permissionRepo->create([
            'user_id' => $userId,
            'folder_id' => $folderId,
            'permission_id' => 1
        ]);
    }


    public function findItemsForFolder(Folder $folder)
    {
        return $this->folderRepo->findItemsForFolder($folder);
    }

    public function updateFolderInfo(Folder $folder, $data)
    {
        if (array_key_exists('parent_id', $data)) {
            if ($this->canMoveFolder($folder, $data['parent_id']) && $this->hasAuthorize($folder->user_id, $data['parent_id'])) {
                $parentList = $this->modifyParentOfChilderns($folder, $data['parent_id']);
                $this->folderRepo->update($folder, ['parent_id' => $data['parent_id'], 'parents_list' => $parentList]);
            } else {
                return false;
            }
            // return false;
        }
        if (array_key_exists('name', $data)) {
            $count = $this->folderRepo->findByName($folder->user_id, $data['name'], $data['parent_id']);
            if ($count > 0) {
                $data['name'] = $data['name'] . 'Copy (' . ++$count . ')';
            }
            return $this->folderRepo->update($folder, ['name' => $data['name']]);
        }
        return true;
    }

    public function modifyParentOfChilderns(Folder $folder, ?int $newParent)
    {

        if ($newParent) {
            $parent = $this->folderRepo->findById($newParent);
            $this->folderRepo->moveFolder($folder, $parent);
            $newParentList = $parent->parents_list . $newParent . '/';
        } else {
            $newParentList = '';
        }

        $parentList =  $folder->parents_list . $folder->id . '/';

        $this->folderRepo->updateParentsListForChilderns($parentList, $newParentList, $folder->id);

        return $newParentList;
    }




    public function canMoveFolder(Folder $folder, ?int $newParentId)
    {
        // if move folder to root 
        if (!$newParentId) {
            return true;
        }

        // if move folder in it 
        if ($folder->id === $newParentId) {
            return false;
        }

        $parent = Folder::find($newParentId);

        $parentList = $parent->parents_list ?? '';

        if (in_array($folder->id, explode('/', $parentList))) {
            return false;
        }
        return true;
    }

    public function deleteFolder(Folder $folder)
    {
        $this->folderRepo->decrementSizeForFolder($folder->size, $folder);
        $files = $this->folderRepo->findFilesForFolder($folder);
        foreach ($files as $file) {
            if (Storage::disk('local')->exists('files/' . $file->name)) {
                Storage::disk()->delete('files/' . $file->name);
            }
        }

        $this->folderRepo->delete($folder);
    }

    public function hasAuthorize($userId, ?int $folderId)
    {
        // $authUserId = Auth::user()->id;
        if (!$folderId) {
            return true;
        }

        $folder = $this->folderRepo->findById($folderId);
        // $hasPermission = $this->permissionRepo->userHasPermission();

        if ($userId === $folder->user_id) {
            return true;
        }
        return false;
    }

    public function canCreateInFolder($userId, $folderId)
    {
        $folder = $this->folderRepo->findById($folderId);

        return $folder->user_id === $userId
            || $this->permissionRepo->userHasPermission($userId, $folderId, 2);
    }

    public function downloadZipFolder(Folder $folder)
    {
        $zipFileName = 'download.zip';
        $zip = new ZipArchive();

        $allFolders = Folder::pluck('name', 'id')->toArray();

        $ids = $this->folderRepo->findChildrensIdsByParentFolder($folder);
        $childernFolders = Folder::whereIn('id', $ids)->get();

        $folderIds = $ids;
        $folderIds[] = $folder->id;

        $parentsFolders = array_filter(explode('/', $folder->parents_list), fn($id) => strlen($id) > 0);
        $baseFolderIndex = count($parentsFolders);

        $childernFiles = File::whereIn('folder_id', $folderIds)->get();


        if ($zip->open(public_path($zipFileName), ZipArchive::CREATE) === true) {

            $zip->addEmptyDir($folder->name);

            foreach ($childernFolders as $childFolder) {
                $folderPathInZip = $this->buildPathFromParentsList($childFolder, $allFolders, $baseFolderIndex);
                $zip->addEmptyDir($folderPathInZip);
            }

            foreach ($childernFiles as $file) {

                if ($file->folder_id === $folder->id) {
                    $filePath = storage_path('app/private/files/' . $file->name);
                    $zip->addFile($filePath,  $folder->name . '/' . $file->name);
                    continue;
                }

                $filePathInZip = $this->buildPathFromParentsList($file->folder, $allFolders, $baseFolderIndex);
                $filePath = storage_path('app/private/files/' . $file->name);

                $zip->addFile($filePath, $filePathInZip . '/' . $file->name);
            }

            $zip->close();

            return response()->download(public_path($zipFileName))->deleteFileAfterSend(true);
        } else {
            return JsonResponseHelper::errorResponse('Failed to create the zip folder.');
        }
    }

    public function buildPathFromParentsList(Folder $folder, array $allFolders, $baseIdIndex)
    {

        if (!$folder->parents_list) {
            return $folder->name;
        }

        $ids = array_filter(explode('/', $folder->parents_list), fn($id) => strlen($id) > 0);

        $ids = array_slice($ids, $baseIdIndex);

        $names = array_map(fn($id) => $allFolders[$id], $ids);

        $names[] = $folder->name;

        $result = implode('/', $names);

        return $result;
    }

    // public function addFolderToZip(ZipArchive $zip, Folder $folder, string $pathInZip)
    // {
    //     $zip->addEmptyDir($pathInZip);

    //     foreach ($folder->files as $file) {
    //         $filePath = storage_path('app/private/files/' . $file->name);

    //         $zip->addFile($filePath, $pathInZip . '/' . $file->name);
    //     }

    //     foreach ($folder->childern as $folder) {
    //         $this->addFolderToZip($zip, $folder, $pathInZip . '/' . $folder->name);
    //     }
    // }
}
