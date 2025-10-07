<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Folder extends Model
{
    // protected $casts = [
    //     'parents_list' => 'array',
    // ];

    protected $fillable = [
        'parent_id',
        'name',
        'parents_list',
        // 'path',
        'user_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function childern(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function getPathAttribute()
    {

        $parentIds = explode('/', $this->parents_list);

        $parentNames = Folder::whereIn('id', $parentIds)->pluck('name')->toArray();

        return  '/' . implode('/', $parentNames);
    }
}
