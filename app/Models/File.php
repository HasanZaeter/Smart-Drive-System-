<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class File extends Model
{
    protected $fillable = [
        'name',
        'folder_id',
        'path',
        'user_id',
        'size',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPathAttribute()
    {
        if ($this->folder) {
            $parentIds = explode('/', $this->folder->parents_list);
            if (count($parentIds) >= 2) {
                return $this->folder->path . '/' . $this->folder->name . '/';
            }
            return $this->folder->path  . $this->folder->name . '/';
        }

        return '/';
    }
}
