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
        Schema::create('ingaz_delegations', function (Blueprint $table) {
            $table->id();
            $table->date('delegation_date_gregorian');
            $table->string('delegation_date_hijri', 50);

            $table->foreignId('nationality_id')->constrained('nationalities')->restrictOnDelete();
            $table->foreignId('profession_id')->constrained('job_titles')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('maid_id')->nullable()->constrained('maids')->nullOnDelete();
            $table->foreignId('marketer_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->string('client_source_type', 30)->default('existing_in_program');

            $table->string('visa_type')->default('electronic_ingaz_delegation');
            $table->string('worker_name');
            $table->string('worker_passport_number', 100);
            $table->date('worker_passport_issue_date')->nullable();
            $table->string('visa_number', 100);
            $table->date('visa_date')->nullable();
            $table->string('worker_religion', 30)->default('unspecified');
            $table->string('worker_experience', 30)->default('unspecified');

            $table->string('external_office_name');
            $table->string('external_office_license_number', 100)->nullable();
            $table->string('external_office_address', 500)->nullable();
            $table->string('external_office_phone', 50)->nullable();

            $table->string('ingaz_number', 100)->nullable();
            $table->string('msaned_contract_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['nationality_id', 'profession_id']);
            $table->index(['user_id', 'client_source_type']);
            $table->index('maid_id');
            $table->index('ingaz_number');
            $table->index('visa_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingaz_delegations');
    }
};
