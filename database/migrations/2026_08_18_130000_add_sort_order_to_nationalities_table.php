<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('nationalities', 'sort_order')) {
            Schema::table('nationalities', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')
                    ->default(0)
                    ->after('phone_code')
                    ->index();
            });
        }

        DB::table('nationalities')
            ->where('sort_order', 0)
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id, $index) {
                DB::table('nationalities')
                    ->where('id', $id)
                    ->where('sort_order', 0)
                    ->update(['sort_order' => $index + 1]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('nationalities', 'sort_order')) {
            Schema::table('nationalities', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
