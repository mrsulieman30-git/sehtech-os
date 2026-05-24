<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'role_id',
        'permission',
    ];

    public function fileNode(): BelongsTo
    {
        return $this->belongsTo(FileNode::class, 'file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
