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
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('required_maid_religion_id')->nullable()->after('required_maid_nationality_id')->comment('الدين المطلوب للعاملة');
            $table->integer('required_maid_age_min')->nullable()->after('required_maid_religion_id')->comment('الحد الأدنى للعمر');
            $table->integer('required_maid_age_max')->nullable()->after('required_maid_age_min')->comment('الحد الأقصى للعمر');
            $table->integer('required_maid_experience_min')->nullable()->after('required_maid_age_max')->comment('الحد الأدنى للخبرة');
            $table->unsignedBigInteger('required_maid_job_title_id')->nullable()->after('required_maid_experience_min')->comment('المهنة المطلوبة');
            $table->text('required_maid_notes')->nullable()->after('required_maid_job_title_id')->comment('ملاحظات إضافية');

            // إضافة foreign keys
            $table->foreign('required_maid_religion_id')->references('id')->on('religions')->onDelete('set null');
            $table->foreign('required_maid_job_title_id')->references('id')->on('job_titles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['required_maid_religion_id']);
            $table->dropForeign(['required_maid_job_title_id']);
            $table->dropColumn([
                'required_maid_religion_id',
                'required_maid_age_min',
                'required_maid_age_max',
                'required_maid_experience_min',
                'required_maid_job_title_id',
                'required_maid_notes'
            ]);
        });
    }
};
