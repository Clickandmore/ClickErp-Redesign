<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guideline_sections', function (Blueprint $table) {
            $table->id();
            $table->string('client_file_path', 2048)->nullable();
            $table->string('worker_file_path', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('guideline_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guideline_section_id')
                ->constrained('guideline_sections')
                ->cascadeOnDelete();
            $table->string('youtube_url', 2048);
            $table->timestamps();
        });

        Schema::create('nationality_guidelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guideline_section_id')
                ->constrained('guideline_sections')
                ->cascadeOnDelete();
            $table->foreignId('nationality_id')
                ->constrained('nationalities')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(
                ['guideline_section_id', 'nationality_id'],
                'guideline_section_nationality_unique'
            );
        });

        Schema::create('nationality_guideline_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nationality_guideline_id')
                ->constrained('nationality_guidelines')
                ->cascadeOnDelete();
            $table->string('language');
            $table->string('client_file_path', 2048);
            $table->string('worker_file_path', 2048);
            $table->timestamps();
            $table->unique(
                ['nationality_guideline_id', 'language'],
                'nationality_guideline_language_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nationality_guideline_versions');
        Schema::dropIfExists('nationality_guidelines');
        Schema::dropIfExists('guideline_videos');
        Schema::dropIfExists('guideline_sections');
    }
};
