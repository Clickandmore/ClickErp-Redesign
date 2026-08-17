<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_contract_stage_steps', function (Blueprint $table) {
            $table->id();
            $table->string('stage', 80)->index();
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage', 'nationality_id', 'sort_order'], 'recruitment_stage_steps_lookup_index');
        });

        Schema::create('recruitment_contract_step_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('stage_step_id')->constrained('recruitment_contract_stage_steps')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'stage_step_id'], 'recruitment_contract_step_completion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_contract_step_completions');
        Schema::dropIfExists('recruitment_contract_stage_steps');
    }
};
