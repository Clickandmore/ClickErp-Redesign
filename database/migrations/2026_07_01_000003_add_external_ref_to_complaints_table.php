<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'external_ref')) {
                $table->string('external_ref')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'external_ref')) {
                $table->dropIndex(['external_ref']);
                $table->dropColumn('external_ref');
            }
        });
    }
};
