<?php

namespace Database\Seeders;

use App\Models\ScheduledRoutine;
use Illuminate\Database\Seeder;

class ScheduledRoutineSeeder extends Seeder
{
    public function run(): void
    {
        $routines = [
            [
                'title' => 'Email check (alex@bedigital.si)',
                'description' => 'Check inbox for new emails, reply or flag important ones',
                'schedule_time' => '07:00-22:00',
                'schedule_type' => 'hourly',
                'frequency' => 'Hourly 7-22',
                'assigned_to' => 'alex',
                'enabled' => true,
                'category' => 'email',
                'position' => 0,
            ],
            [
                'title' => 'Email check (alex.bedigital@gmail.com)',
                'description' => 'Check Gmail inbox for new emails',
                'schedule_time' => '07:00-22:00',
                'schedule_type' => 'hourly',
                'frequency' => 'Hourly 7-22',
                'assigned_to' => 'alex',
                'enabled' => true,
                'category' => 'email',
                'position' => 1,
            ],
            [
                'title' => 'WooCommerce order check (4 stores)',
                'description' => 'Check zavedno.si, zauvijek.hr, wiecznie.pl, vecny.cz for new/processing orders',
                'schedule_time' => '07:30',
                'schedule_type' => 'daily',
                'frequency' => 'Daily 7:30',
                'assigned_to' => 'alex',
                'enabled' => true,
                'category' => 'orders',
                'position' => 2,
            ],
            [
                'title' => 'GLS pickup SMS reminders',
                'description' => 'Send SMS reminders for GLS package pickups',
                'schedule_time' => '09:00',
                'schedule_type' => 'daily',
                'frequency' => 'Daily 9:00',
                'assigned_to' => 'alex',
                'enabled' => true,
                'category' => 'sms',
                'position' => 3,
            ],
            [
                'title' => 'Heartbeat checks (rotating)',
                'description' => 'Rotate through inbox, calendar, weather, mentions',
                'schedule_time' => '07:00-22:00',
                'schedule_type' => 'interval',
                'frequency' => 'Every 30min',
                'assigned_to' => 'alex',
                'enabled' => true,
                'category' => 'monitoring',
                'position' => 4,
            ],
            [
                'title' => 'Taskboard check (tasks for alex)',
                'description' => 'Check kanban board for assigned tasks and update progress',
                'schedule_time' => '07:00-22:00',
                'schedule_type' => 'hourly',
                'frequency' => 'Hourly 7-22',
                'assigned_to' => 'alex',
                'enabled' => true,
                'category' => 'monitoring',
                'position' => 5,
            ],
        ];

        foreach ($routines as $routine) {
            ScheduledRoutine::create($routine);
        }
    }
}
