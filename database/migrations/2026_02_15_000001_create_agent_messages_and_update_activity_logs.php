<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->string('from_agent');
            $table->string('to_agent');
            $table->text('message');
            $table->text('response')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasColumn('activity_logs', 'agent')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->string('agent')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
        if (Schema::hasColumn('activity_logs', 'agent')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('agent');
            });
        }
    }
};
