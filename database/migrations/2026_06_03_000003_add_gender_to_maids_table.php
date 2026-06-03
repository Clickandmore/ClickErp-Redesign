<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            if (!Schema::hasColumn('maids', 'gender')) {
                $table->string('gender', 10)->default('female')->after('name')->comment('male or female');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            if (Schema::hasColumn('maids', 'gender')) {
                $table->dropColumn('gender');
            }
        });
    }
};
