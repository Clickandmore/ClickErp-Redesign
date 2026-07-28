<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_hero_sections', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->string('video_type')->nullable();
            $table->string('video_path', 2048)->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('home_hero_section_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_hero_section_id')
                ->constrained('home_hero_sections')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->text('subtitle');
            $table->string('video_title')->nullable();
            $table->timestamps();
            $table->unique(['home_hero_section_id', 'locale'], 'home_hero_section_locale_unique');
        });

        Schema::create('home_hero_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_hero_section_id')
                ->constrained('home_hero_sections')
                ->cascadeOnDelete();
            $table->string('path', 2048);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_hero_images');
        Schema::dropIfExists('home_hero_section_translations');
        Schema::dropIfExists('home_hero_sections');
    }
};
