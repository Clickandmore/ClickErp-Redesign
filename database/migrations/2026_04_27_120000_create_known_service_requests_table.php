<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('known_service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('client_name');
            $table->string('client_phone', 50);
            $table->string('client_email')->nullable();
            $table->string('client_address')->nullable();
            $table->string('city')->nullable();
            $table->string('worker_name');
            $table->unsignedBigInteger('nationality_id')->nullable()->index();
            $table->string('passport_number', 100);
            $table->date('passport_expiration_date')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->unsignedBigInteger('social_status_id')->nullable()->index();
            $table->unsignedBigInteger('religion_id')->nullable()->index();
            $table->text('skills')->nullable();
            $table->decimal('required_salary', 10, 2)->nullable();
            $table->string('worker_phone', 50)->nullable();
            $table->string('worker_country_address')->nullable();
            $table->string('relative_in_saudi_name')->nullable();
            $table->string('relative_in_saudi_phone', 50)->nullable();
            $table->string('worker_passport_copy')->nullable();
            $table->string('worker_personal_photo')->nullable();
            $table->string('client_identity_copy')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('known_service_requests');
    }
};
