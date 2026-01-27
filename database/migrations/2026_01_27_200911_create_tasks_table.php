<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['backlog', 'todo', 'in_progress', 'done'])->default('backlog');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('assigned_to', ['sandi', 'alex'])->nullable();
            $table->date('due_date')->nullable();
            $table->json('tags')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes for better performance
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
