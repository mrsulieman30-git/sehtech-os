<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileNode extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'files';

    protected $fillable = [
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'directory_id',
        'department_id',
        'access_level',
        'version',
        'parent_version_id',
        'is_deleted',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'size' => 'integer',
        'version' => 'integer',
        'is_deleted' => 'boolean',
        'meta' => 'array',
    ];

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(FileNode::class, 'parent_version_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FilePermission::class, 'file_id');
    }
}
