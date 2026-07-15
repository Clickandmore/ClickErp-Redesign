<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_batch_items')) {
            return;
        }

        Schema::create('commission_batch_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('ingaz_delegation_id')->nullable();
            $table->string('service_type')->nullable();
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->enum('method', ['percentage', 'fixed']);
            $table->decimal('rate_or_amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('commission_batches')->cascadeOnDelete();
            $table->foreign('ingaz_delegation_id')->references('id')->on('ingaz_delegations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_batch_items');
    }
};
