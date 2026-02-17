<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_routines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('schedule_time'); // e.g. "09:00", "07:00-22:00"
            $table->string('schedule_type'); // daily, hourly, interval, manual
            $table->string('frequency')->nullable(); // e.g. "every 30min", "hourly 7-22"
            $table->string('assigned_to')->default('alex');
            $table->boolean('enabled')->default(true);
            $table->string('category'); // email, sms, orders, analysis, monitoring, other
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_routines');
    }
};
