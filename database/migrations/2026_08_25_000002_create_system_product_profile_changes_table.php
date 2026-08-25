<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_product_profile_changes', function (Blueprint $table) {
            $table->id();
            $table->string('from_profile', 40);
            $table->string('to_profile', 40);
            $table->string('changed_by', 120)->nullable();
            $table->text('reason');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_product_profile_changes');
    }
};
