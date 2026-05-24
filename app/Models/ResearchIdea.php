<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchIdea extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'summary',
        'content',
        'category',
        'status',
        'vote_count',
        'author_id',
        'converted_project_id',
        'meta',
        'priority',
        'tags',
    ];

    protected $casts = [
        'vote_count' => 'integer',
        'meta' => 'array',
        'tags' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ResearchComment::class, 'idea_id')->orderBy('created_at', 'asc');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }
}
