<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('commission_settings', function (Blueprint $table) {
            $table->string('rental_return_policy', 30)->default('full');
        });
        Schema::table('commission_batch_items', function (Blueprint $table) {
            $table->json('rental_commission_terms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('commission_batch_items', fn (Blueprint $table) => $table->dropColumn('rental_commission_terms'));
        Schema::table('commission_settings', fn (Blueprint $table) => $table->dropColumn('rental_return_policy'));
    }
};
