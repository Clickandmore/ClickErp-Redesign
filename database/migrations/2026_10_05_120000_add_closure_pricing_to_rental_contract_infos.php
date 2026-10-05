<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            $table->json('closure_pricing')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            $table->dropColumn('closure_pricing');
        });
    }
};
