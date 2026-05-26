<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileAccessGrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'path',
        'access_level',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
