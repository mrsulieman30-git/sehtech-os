<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNode extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'type',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(ProjectNode::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectNode::class, 'parent_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'node_id');
    }
}
