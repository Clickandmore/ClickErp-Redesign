<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_sponsorship_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_transfer_id')->index();
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('maid_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('current_sponsor_id');
            $table->unsignedBigInteger('company_profile_id');
            $table->unsignedBigInteger('shelter_id');
            $table->string('status')->default('pending')->index();
            $table->date('request_date');
            $table->text('notes')->nullable();
            $table->json('history');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_sponsorship_returns');
    }
};
