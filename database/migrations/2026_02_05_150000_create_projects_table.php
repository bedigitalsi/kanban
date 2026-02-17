<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, paused, completed, archived
            $table->string('icon')->nullable(); // emoji or icon name
            $table->string('color')->default('#13b6ec'); // hex color for card accent
            
            // URLs and links
            $table->string('url')->nullable(); // production URL
            $table->string('staging_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('docs_url')->nullable();
            
            // Technical details (JSON for flexibility)
            $table->json('tech_stack')->nullable(); // ["Laravel", "Vue", "MySQL"]
            $table->json('api_details')->nullable(); // { base_url, token_hint, endpoints }
            $table->json('credentials')->nullable(); // { api_key_hint, notes }
            $table->json('contacts')->nullable(); // [{ name, role, email }]
            
            // Notes
            $table->text('notes')->nullable(); // markdown supported
            $table->text('quick_reference')->nullable(); // key commands, shortcuts
            
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        // Link tasks to projects (optional)
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
        
        Schema::dropIfExists('projects');
    }
};
