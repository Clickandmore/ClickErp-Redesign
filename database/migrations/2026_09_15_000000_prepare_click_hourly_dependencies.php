<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Add only missing nullable integration fields; never rewrite existing Click data. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('shelter_places', function (Blueprint $table) {
            if (!Schema::hasColumn('shelter_places', 'sort_order')) $table->unsignedInteger('sort_order')->default(0);
            if (!Schema::hasColumn('shelter_places', 'city')) $table->string('city')->nullable();
            if (!Schema::hasColumn('shelter_places', 'is_active')) $table->boolean('is_active')->default(true);
        });
        Schema::table('maids', function (Blueprint $table) {
            if (!Schema::hasColumn('maids', 'auto_recruitment_contract_id')) $table->unsignedBigInteger('auto_recruitment_contract_id')->nullable();
            if (!Schema::hasColumn('maids', 'rental_source')) $table->string('rental_source', 30)->nullable();
            if (!Schema::hasColumn('maids', 'arrival_date')) $table->date('arrival_date')->nullable();
        });
        Schema::table('user_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('user_addresses', 'city_id')) $table->unsignedBigInteger('city_id')->nullable()->index();
            if (!Schema::hasColumn('user_addresses', 'latitude')) $table->decimal('latitude', 10, 7)->nullable();
            if (!Schema::hasColumn('user_addresses', 'longitude')) $table->decimal('longitude', 10, 7)->nullable();
            foreach (['district', 'building', 'unit', 'contact_phone'] as $field) {
                if (!Schema::hasColumn('user_addresses', $field)) $table->string($field)->nullable();
            }
        });
        if (!Schema::hasTable('communication_logs')) Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->string('channel', 30)->index();
            $table->string('recipient_type', 30)->nullable();
            foreach (['user_id', 'admin_id', 'notification_id'] as $field) $table->unsignedBigInteger($field)->nullable()->index();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 32)->nullable()->index();
            $table->text('title')->nullable();
            $table->longText('message');
            $table->string('provider')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->longText('response')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Shared columns/logs may be used elsewhere after installation. Retain them and their data.
    }
};
