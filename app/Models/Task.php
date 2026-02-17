<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'due_date',
        'tags',
        'position',
        'project_id',
        'board',
    ];

    protected $casts = [
        'tags' => 'array',
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Scope for filtering by board
    public function scopeByBoard($query, $board)
    {
        return $query->where('board', $board);
    }

    // Scope for filtering by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope for filtering by priority
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // Scope for filtering by assignee
    public function scopeAssignedTo($query, $assignee)
    {
        return $query->where('assigned_to', $assignee);
    }

    // Get tasks ordered by position
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('created_at');
    }

    // Get priority color for badges
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'high' => 'bg-red-100 text-red-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Get priority emoji
    public function getPriorityEmojiAttribute()
    {
        return match($this->priority) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪'
        };
    }

    // Get assignee avatar/name for display
    public function getAssigneeDisplayAttribute()
    {
        return match($this->assigned_to) {
            'sandi' => ['name' => 'Sandi', 'avatar' => 'S', 'color' => 'bg-blue-500'],
            'alex' => ['name' => 'Alex', 'avatar' => 'A', 'color' => 'bg-green-500'],
            default => null
        };
    }
}
