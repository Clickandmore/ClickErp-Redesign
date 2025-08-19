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
        Schema::create('istiqdam_contracts', function (Blueprint $table) {
            $table->id();

            // Contract identifiers
            $table->string('istiqdam_contract_id')->unique(); // ID from Istiqdam platform
            $table->string('contract_number')->nullable(); // Internal contract number

            // Package information
            $table->unsignedBigInteger('package_id')->nullable();
            $table->json('package_details')->nullable(); // Store package information

            // Beneficiary information
            $table->string('beneficiary_id'); // National ID
            $table->date('beneficiary_birthdate');
            $table->string('beneficiary_name')->nullable();

            // Contract dates
            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();
            $table->datetime('created_at_istiqdam')->nullable(); // When created in Istiqdam platform

            // Contract status
            $table->enum('status', [
                'draft',
                'active',
                'completed',
                'cancelled',
                'expired',
                'pending_approval',
                'rejected'
            ])->default('draft');

            // Contact information
            $table->json('contact_details')->nullable(); // Store all contact information
            $table->json('address_details')->nullable(); // Store address information

            // Payment information
            $table->json('payment_details')->nullable(); // Store payment information
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->nullable();

            // Commitments and terms
            $table->json('commitments')->nullable(); // Store all commitments

            // Work permit and maid assignment
            $table->string('work_permit_number')->nullable();
            $table->unsignedBigInteger('assigned_maid_id')->nullable();
            $table->datetime('maid_assigned_at')->nullable();

            // Additional information
            $table->text('special_specification')->nullable();
            $table->text('notes')->nullable();

            // API response storage
            $table->json('last_api_response')->nullable(); // Store last response from Istiqdam API
            $table->datetime('last_synced_at')->nullable(); // Last sync with Istiqdam platform

            // Tracking fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('assigned_maid_id')->references('id')->on('maids')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['beneficiary_id']);
            $table->index(['package_id']);
            $table->index(['assigned_maid_id']);
            $table->index(['last_synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('istiqdam_contracts');
    }
};
