<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'icon',
        'color',
        'url',
        'staging_url',
        'github_url',
        'docs_url',
        'tech_stack',
        'api_details',
        'credentials',
        'contacts',
        'notes',
        'quick_reference',
        'position',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'api_details' => 'array',
        'credentials' => 'array',
        'contacts' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
            if (is_null($project->position)) {
                $project->position = static::max('position') + 1;
            }
        });
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function activeTasks()
    {
        return $this->tasks()->whereIn('status', ['backlog', 'todo', 'in_progress']);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => '🟢',
            'paused' => '🟡',
            'completed' => '✅',
            'archived' => '📦',
            default => '⚪',
        };
    }
}
