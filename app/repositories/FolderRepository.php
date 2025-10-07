<?php

namespace App\Repositories;

use App\Http\Resources\FileResource;
use App\Http\Resources\FolderResource;
use App\Models\File;
use App\Models\Folder;
use FFI;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class FolderRepository
{
    public function getRootItems()
    {

        $folders = Folder::whereNull('parent_id')->where('user_id', Auth::user()->id)->get();

        $files = File::whereNull('folder_id')->where('user_id', Auth::user()->id)->get();

        return [
            'folders' => FolderResource::collection($folders),
            'files' => FileResource::collection($files)
        ];
    }

    public function findByName($userId, $name, $parentId)
    {
        return Folder::where('name', $name)
            ->where('user_id', $userId)
            ->where(function ($query) use ($parentId) {
                if (is_null($parentId)) {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $parentId);
                }
            })->count();
    }

    public function findById($folderId)
    {
        return Folder::where('id', $folderId)->first();
    }

    public function findFoldersByParentID($parentId)
    {
        return Folder::whereJsonContains('parents_list', $parentId)->get();
    }

    public function create(array $data)
    {
        return Folder::create($data);
    }

    public function update(Folder $folder, array $data)
    {
        return $folder->update($data);
    }

    public function updateParentsListForChilderns($parentList, $newParentList, $folderId)
    {
        DB::table('folders')
            ->where('parents_list', 'like',  $parentList . '%')
            ->update([
                'parents_list' =>  DB::raw("REPLACE(parents_list,'$parentList','$newParentList$folderId/')")
            ]);
    }

    public function findChildrensIdsByParentFolder(Folder $folder)
    {
        $parents_List = $folder->parents_list . $folder->id . '/';

        // Log::info($parents_List);
        return DB::table('folders')
            ->where('parents_list', 'like', $parents_List . '%')
            ->pluck('id')
            ->toArray();
    }

    public function findItemsForFolder(Folder $folder)
    {
        $folders = Folder::where('parent_id', $folder->id)->where('user_id', $folder->user_id)->get();

        $files = File::where('folder_id', $folder->id)->where('user_id', $folder->user_id)->get();

        return [
            'folders' => FolderResource::collection($folders),
            'files' => FileResource::collection($files)
        ];
    }

    public function findFilesForFolder(Folder $folder)
    {
        return File::where('folder_id', $folder->id)->get();
    }

    public function delete(Folder $folder)
    {
        $folder->delete();
    }

    public function incrementsSizeInFolders($size, Folder $folder)
    {
        if (is_null($folder->parent_id)) {
            DB::table('folders')->where('id', $folder->id)->increment('size', $size);
        } else {
            $ids = explode('/', $folder->parents_list);
            $ids[] = $folder->id;

            DB::table('folders')->whereIn('id', $ids)->increment('size', $size);
        }
    }

    public function decrementSizeForFolders($size, Folder $folder)
    {
        if (is_null($folder->parent_id)) {
            DB::table('folders')->where('id', $folder->id)->decrement('size', $size);
        } else {
            $ids = explode('/', $folder->parents_list);
            $ids[] = $folder->id;
            DB::table('folders')->whereIn('id', $ids)->decrement('size', $size);
        }
    }

    public function decrementSizeForFolder($size, Folder $folder)
    {
        if (is_null($folder->parent_id)) {
            DB::table('folders')->where('id', $folder->id)->decrement('size', $size);
        } else {
            $ids = explode('/', $folder->parents_list);
            DB::table('folders')->whereIn('id', $ids)->decrement('size', $size);
        }
    }

    public function updateFileSize($oldSize, $newSize, Folder $folder)
    {
        $this->decrementSizeForFolders($oldSize, $folder);
        $this->incrementsSizeInFolders($newSize, $folder);
    }

    public function moveFileFromFolder($fileSize, Folder $oldFolder, Folder $newFolder)
    {
        $this->decrementSizeForFolders($fileSize, $oldFolder);
        $this->incrementsSizeInFolders($fileSize, $newFolder);
    }

    public function moveFolder(Folder $folder, Folder $newFolder)
    {
        $this->decrementSizeForFolder($folder->size, $folder);
        $this->incrementsSizeInFolders($folder->size, $newFolder);
    }
}
