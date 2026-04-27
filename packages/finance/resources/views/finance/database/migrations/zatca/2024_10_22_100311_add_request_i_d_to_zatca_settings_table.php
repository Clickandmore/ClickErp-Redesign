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
        Schema::table('zatca_settings', function (Blueprint $table) {
            $table->string('requestID')->nullable()->after('csr_expire_date');
            $table->text('binarySecurityToken')->nullable()->after('csr_expire_date');
            $table->text('secret')->nullable()->after('csr_expire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zatca_settings', function (Blueprint $table) {
            //
        });
    }
};
