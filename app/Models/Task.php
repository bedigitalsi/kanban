<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'description', 'status', 'priority', 'assigned_to',
        'due_date', 'tags', 'position', 'project_id', 'project', 'board',
    ];

    protected $casts = [
        'tags' => 'array',
        'due_date' => 'date',
    ];

    public function scopeBoard($query, $board)
    {
        return $query->where('board', $board);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssignedTo($query, $user)
    {
        return $query->where('assigned_to', $user);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
