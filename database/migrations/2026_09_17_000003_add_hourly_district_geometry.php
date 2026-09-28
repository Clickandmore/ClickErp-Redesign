<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hourly_service_districts', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
            $t->json('polygon')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('hourly_service_districts', fn (Blueprint $t) => $t->dropColumn(['is_active','polygon']));
    }
};
