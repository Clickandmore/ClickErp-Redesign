<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hourly_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('employee_number', 50)->unique();
            $table->string('gender', 10);
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone', 30);
            $table->string('phone2', 30)->nullable();
            foreach (['birth_date', 'work_start_date', 'insurance_expiry_date', 'contract_start_date', 'contract_end_date', 'replacement_expiry_date', 'residency_expiry_date', 'passport_expiry_date', 'entry_date'] as $field) {
                $table->date($field)->nullable();
            }
            $table->string('residency_number', 50)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('border_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('vehicle_capacity');
            $table->json('work_schedule');
            $table->boolean('is_active')->default(true);
            $table->boolean('account_enabled')->default(true);
            $table->string('login_access', 10)->default('both');
            $table->string('username', 50)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('login_phone', 20)->nullable()->unique();
            $table->string('password')->nullable();
            $table->unsignedInteger('auth_version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_drivers');
    }
};
