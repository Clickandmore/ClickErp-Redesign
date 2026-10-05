<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_client_id')->nullable();
            $table->json('company_legacy_client_ids')->nullable();
            $table->string('company_license_number', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['company_client_id', 'company_legacy_client_ids', 'company_license_number']);
        });
    }
};
