<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fn_constraint_logs')) {
            return;
        }

        Schema::create('fn_constraint_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constraint_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('action', 50);
            $table->string('description', 500)->nullable();
            $table->longText('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->foreign('constraint_id')
                ->references('id')
                ->on('fn_constraints')
                ->cascadeOnDelete();
            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
            $table->index(['constraint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fn_constraint_logs');
    }
};
