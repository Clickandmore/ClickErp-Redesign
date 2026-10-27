<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->unsignedBigInteger('maid_id')->nullable()->index();
            $table->string('maid_registration_status', 20)->nullable();
            $table->string('maid_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', fn (Blueprint $table) => $table->dropColumn(['maid_id', 'maid_registration_status', 'maid_name']));
    }
};
