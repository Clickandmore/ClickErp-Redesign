<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('musaned_sections', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->decimal('allowed_period_commitment_rate', 5, 2)->default(0);
            $table->decimal('workforce_arrival_rate', 5, 2)->default(0);
            $table->decimal('complaint_handling_rate', 5, 2)->default(0);
            $table->decimal('arrival_duration_commitment_rate', 5, 2)->default(0);
            $table->decimal('customer_recommendation_rating', 3, 2)->default(0);
            $table->decimal('inquiry_response_rating', 3, 2)->default(0);
            $table->decimal('recruitment_duration_rating', 3, 2)->default(0);
            $table->decimal('complaint_response_rating', 3, 2)->default(0);
            $table->string('profile_url', 2048);
            $table->timestamps();
        });

        Schema::create('musaned_section_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musaned_section_id')
                ->constrained('musaned_sections')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->text('subtitle');
            $table->string('overall_performance');
            $table->timestamps();
            $table->unique(['musaned_section_id', 'locale'], 'musaned_section_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musaned_section_translations');
        Schema::dropIfExists('musaned_sections');
    }
};
