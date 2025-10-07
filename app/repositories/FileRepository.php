<?php

namespace App\Repositories;

use App\Helpers\JsonResponseHelper;
use App\Models\File;
use App\Models\Folder;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileRepository
{
    public function uploadFile($folderId, UploadedFile $file)
    {
        $originalName = $file->getClientOriginalName();
        $filename = $originalName;
        $folder = 'files';

        if (Storage::disk('local')->exists($folder . '/' . $filename)) {
            $filename = time() . '_' . $originalName;
        }

        $file->storeAs('files', $filename, 'local');

        try {
            return File::create([
                'name' => $filename,
                'user_id' => Auth::user()->id,
                'folder_id' => $folderId,
                'size' => $file->getSize()
            ]);
        } catch (Exception $e) {
            return JsonResponseHelper::errorResponse($e, [], 500);
        }
    }

    public function update(File $file, $data)
    {
        return $file->update($data);
    }

    public function delete(File $file)
    {
        $file->delete();
    }

    public function findByFolderId($folderId)
    {
        return File::find('folder_id', $folderId)->first();
    }
}
