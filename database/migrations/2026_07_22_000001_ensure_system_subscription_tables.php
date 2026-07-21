<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_subscriptions')) {
            Schema::create('system_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('cost');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('system_plans')) {
            Schema::create('system_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('system_subscription_id');
                $table->string('module_name');
                $table->timestamps();

                $table->foreign('system_subscription_id')
                    ->references('id')
                    ->on('system_subscriptions')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_plans');
        Schema::dropIfExists('system_subscriptions');
    }
};
