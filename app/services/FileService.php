<?php

namespace App\Services;

use App\Models\File;
use App\Repositories\FileRepository;
use App\Repositories\FolderRepository;
use App\Repositories\PermissionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileService
{
    protected $fileRepo;
    protected $folderRepo, $permissionRepo;
    public function __construct(FileRepository $fileRepository, FolderRepository $folderRepository, PermissionRepository $permissionRepository)
    {
        $this->permissionRepo = $permissionRepository;
        $this->fileRepo = $fileRepository;
        $this->folderRepo = $folderRepository;
    }

    public function createNewFile($data)
    {
        $userId = Auth::id();
        if (!empty($data['folder_id'])) {
            return $this->createInFolder($userId, $data);
        }

        return $this->createInRoot($data);
    }
    public function createInFolder(int $userId, array $data)
    {
        $folder = $this->folderRepo->findById($data['folder_id']);

        if ($folder->user_id !== $userId && !$this->permissionRepo->userHasPermission($userId, $folder->id, 2)) {
            return false;
        }

        $this->folderRepo->incrementsSizeInFolders($data['file']->getSize(), $folder);

        return $this->fileRepo->uploadFile($data['folder_id'], $data['file']);
    }

    protected function createInRoot(array $data)
    {
        // $this->folderRepo->incrementsSizeInFolders($data['file']->getSize());

        return $this->fileRepo->uploadFile(null, $data['file']);
    }

    public function update(File $file, $data)
    {
        if (isset($data['file'])) {
            $newFile = $data['file'];
            $newSize = $newFile->getSize();

            if (Storage::disk('local')->exists('files/' . $file->name)) {
                Storage::disk('local')->delete('files/' . $file->name);
            }

            $originalName = $newFile->getClientOriginalName();
            $filename = $originalName;
            $folder = 'files';

            if (Storage::disk()->exists($folder . '/' . $filename)) {
                $filename = time() . '_' . $originalName;
            }

            $newFile->storeAs('files', $filename, 'local');

            $this->folderRepo->updateFileSize($file->size, $newSize, $file->folder);
            $this->fileRepo->update($file, ['name' => $filename, 'size' => $newSize]);
        }
        if (isset($data['folder_id'])) {
            $newFolder = $this->folderRepo->findById($data['folder_id']);
            $oldFolder = $file->folder;

            if (Auth::user()->id === $newFolder->user_id) {
                $this->fileRepo->update($file, ['folder_id' => $data['folder_id']]);
                $this->folderRepo->moveFileFromFolder($file->size, $oldFolder, $newFolder);
                return true;
            }
            return false;
        }
        return true;
    }

    public function delete(File $file)
    {
        $folder = 'files';

        if (Storage::disk('local')->exists($folder . '/' . $file->name)) {
            Storage::disk('local')->delete($folder . '/' . $file->name);
        }

        $this->folderRepo->decrementSizeForFolders($file->size, $file->folder);
        $this->fileRepo->delete($file);
    }

    public function search(array $data)
    {
        $authUserId = Auth::user()->id;

        $query = File::query()
            ->leftJoin('folders', 'folders.id', '=', 'files.folder_id')
            ->where(function ($query) use ($authUserId) {
                $query->where('folders.user_id', $authUserId)
                    ->orWhereExists(function ($subQuery) use ($authUserId) {
                        $subQuery->select(DB::raw(1))
                            ->from('user_permissions')
                            ->whereColumn('user_permissions.folder_id', 'folders.id')
                            ->where('user_permissions.user_id', $authUserId)
                            ->where('user_permissions.permission_id', 1);
                    });
            });

        $filters = [

            'extension' => fn($query, $value) =>
            $query->where('files.name', 'like', "%.{$value}"),

            'file_name' => fn($query, $value) =>
            $query->where('files.name', 'like', "%{$value}%"),

            'small_size' => fn($query, $value) =>
            $query->where('files.size', '>=', $value),

            'big_size' => fn($query, $value) =>
            $query->where('files.size', '<=', $value),

            'created_at' => fn($query, $value) =>
            $query->whereBetween('files.created_at', [
                $value,
                Carbon::now()
            ]),

            'updated_at' => fn($query, $value) =>
            $query->whereDate('files.updated_at', $value),
        ];

        foreach ($filters as $key => $callback) {
            if (!empty($data[$key])) {
                $callback($query, $data[$key]);
            }
        }

        $result = $query->select('files.*')->get();
        return $result;
    }
}
