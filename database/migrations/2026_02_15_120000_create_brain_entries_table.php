<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brain_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->string('agent')->nullable();
            $table->string('source')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('archived')->default(false);
            $table->timestamps();

            $table->index('category');
            $table->index('pinned');
            $table->index('archived');
            $table->fullText(['title', 'content']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brain_entries');
    }
};
