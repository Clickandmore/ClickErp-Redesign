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
        Schema::create('maid_unavailable_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();

            $table->foreign('maid_id')->references('id')->on('maids')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('set null');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');

            $table->index('maid_id');
            $table->index('contract_id');
            $table->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maid_unavailable_records');
    }
};
