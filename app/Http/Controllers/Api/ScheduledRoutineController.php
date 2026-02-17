<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledRoutine;

class ScheduledRoutineController extends Controller
{
    public function index()
    {
        return ScheduledRoutine::orderBy('position')->get();
    }
}
