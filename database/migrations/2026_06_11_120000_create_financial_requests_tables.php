<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_requests')) {
            Schema::create('financial_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_no')->unique();
                $table->string('type');
                $table->string('status')->default('pending_review');
                $table->string('priority')->default('normal');
                $table->decimal('amount', 12, 2)->nullable();
                $table->date('request_date')->nullable();
                $table->text('description')->nullable();
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('client_name')->nullable();
                $table->string('client_identity')->nullable();
                $table->unsignedBigInteger('maid_id')->nullable();
                $table->string('maid_name')->nullable();
                $table->string('maid_passport')->nullable();
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->string('contract_type')->nullable();
                $table->string('contract_number')->nullable();
                $table->decimal('contract_cost', 12, 2)->nullable();
                $table->decimal('contract_salary', 12, 2)->nullable();
                $table->unsignedBigInteger('finance_bond_id')->nullable();
                $table->string('finance_bond_type')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->unsignedBigInteger('approved_by_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['type', 'status', 'priority'], 'fr_type_status_priority_idx');
                $table->index(['contract_number', 'client_identity', 'created_by_id'], 'fr_contract_client_creator_idx');
            });
        }

        if (! Schema::hasTable('financial_request_attachments')) {
            Schema::create('financial_request_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('financial_request_id');
                $table->string('field_key')->nullable();
                $table->string('label')->nullable();
                $table->text('description')->nullable();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->timestamps();

                $table->foreign('financial_request_id', 'financial_request_attachments_request_fk')
                    ->references('id')
                    ->on('financial_requests')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_request_attachments');
        Schema::dropIfExists('financial_requests');
    }
};
