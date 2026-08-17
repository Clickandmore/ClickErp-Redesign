<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('fn_sale_items', 'line_description')) {
            Schema::table('fn_sale_items', function (Blueprint $table) {
                $table->string('line_description', 500)->nullable()->after('item_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fn_sale_items', 'line_description')) {
            Schema::table('fn_sale_items', function (Blueprint $table) {
                $table->dropColumn('line_description');
            });
        }
    }
};
