<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledRoutine extends Model
{
    protected $fillable = [
        'title', 'description', 'schedule_time', 'schedule_type',
        'frequency', 'assigned_to', 'enabled', 'category', 'position',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
