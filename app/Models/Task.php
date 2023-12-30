<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    public function childTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_edges', 'parent_id', 'child_id');
    }

    public function parentTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_edges', 'child_id', 'parent_id');
    }
}
