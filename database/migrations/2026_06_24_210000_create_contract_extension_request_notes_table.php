<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_extension_request_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extension_request_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->text('note');
            $table->timestamps();

            $table->foreign('extension_request_id')
                  ->references('id')->on('contract_extension_requests')->onDelete('cascade');
            $table->foreign('admin_id')
                  ->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_extension_request_notes');
    }
};
