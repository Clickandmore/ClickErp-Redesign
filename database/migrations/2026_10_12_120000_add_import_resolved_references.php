<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('data_import_rows', fn (Blueprint $table) => $table->json('resolved_references')->nullable());
    }
    public function down(): void
    {
        Schema::table('data_import_rows', fn (Blueprint $table) => $table->dropColumn('resolved_references'));
    }
};
