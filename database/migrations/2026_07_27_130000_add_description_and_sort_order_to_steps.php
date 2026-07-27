<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('steps', 'sort_order')) {
            Schema::table('steps', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')
                    ->default(0)
                    ->after('icon')
                    ->index();
            });
        }

        if (!Schema::hasColumn('steps_translations', 'description')) {
            Schema::table('steps_translations', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }

        DB::table('steps')
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id, $index) {
                DB::table('steps')
                    ->where('id', $id)
                    ->where('sort_order', 0)
                    ->update(['sort_order' => $index + 1]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('steps_translations', 'description')) {
            Schema::table('steps_translations', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasColumn('steps', 'sort_order')) {
            Schema::table('steps', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
