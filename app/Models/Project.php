<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name', 'company', 'slug', 'description', 'status', 'icon', 'color',
        'url', 'staging_url', 'github_url', 'docs_url',
        'tech_stack', 'api_details', 'credentials', 'contacts',
        'notes', 'quick_reference', 'position',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'api_details' => 'array',
        'credentials' => 'array',
        'contacts' => 'array',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
