<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrainEntry extends Model
{
    protected $fillable = [
        'title', 'content', 'category', 'tags', 'agent', 'source', 'pinned', 'archived',
    ];

    protected $casts = [
        'tags' => 'array',
        'pinned' => 'boolean',
        'archived' => 'boolean',
    ];
}
