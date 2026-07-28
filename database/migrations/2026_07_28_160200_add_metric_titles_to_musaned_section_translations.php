<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('musaned_section_translations', function (Blueprint $table) {
            $table->string('allowed_period_commitment_title')->nullable()->after('subtitle');
            $table->string('workforce_arrival_title')->nullable()->after('allowed_period_commitment_title');
            $table->string('complaint_handling_title')->nullable()->after('workforce_arrival_title');
            $table->string('arrival_duration_commitment_title')->nullable()->after('complaint_handling_title');
            $table->string('customer_recommendation_title')->nullable()->after('arrival_duration_commitment_title');
            $table->string('inquiry_response_title')->nullable()->after('customer_recommendation_title');
            $table->string('recruitment_duration_title')->nullable()->after('inquiry_response_title');
            $table->string('complaint_response_title')->nullable()->after('recruitment_duration_title');
        });
    }

    public function down(): void
    {
        Schema::table('musaned_section_translations', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_period_commitment_title',
                'workforce_arrival_title',
                'complaint_handling_title',
                'arrival_duration_commitment_title',
                'customer_recommendation_title',
                'inquiry_response_title',
                'recruitment_duration_title',
                'complaint_response_title',
            ]);
        });
    }
};
