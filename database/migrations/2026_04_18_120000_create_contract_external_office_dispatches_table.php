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
        Schema::create('contract_external_office_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id');
            $table->foreignId('recruitment_office_id')->nullable();
            $table->foreignId('created_by_admin_id')->nullable();
            $table->foreignId('updated_by_admin_id')->nullable();
            $table->date('dispatch_date')->nullable();
            $table->string('contract_number', 100)->nullable();
            $table->string('visa_number', 100)->nullable();
            $table->string('sponsor_name')->nullable();
            $table->string('sponsor_identity_number', 100)->nullable();
            $table->string('worker_name')->nullable();
            $table->string('passport_number', 100)->nullable();
            $table->string('worker_age', 50)->nullable();
            $table->string('worker_experience', 255)->nullable();
            $table->string('worker_religion', 100)->nullable();
            $table->string('office_name')->nullable();
            $table->decimal('office_contract_cost_usd', 10, 2)->nullable();
            $table->string('arrival_station')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->timestamps();

            $table->foreign('contract_id', 'coed_contract_fk')
                ->references('id')
                ->on('contracts')
                ->cascadeOnDelete();
            $table->foreign('recruitment_office_id', 'coed_office_fk')
                ->references('id')
                ->on('recruitment_offices')
                ->nullOnDelete();
            $table->foreign('created_by_admin_id', 'coed_created_by_fk')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
            $table->foreign('updated_by_admin_id', 'coed_updated_by_fk')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();

            $table->unique('contract_id');
            $table->index('dispatch_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_external_office_dispatches');
    }
};
