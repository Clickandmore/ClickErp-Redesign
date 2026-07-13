<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->unsignedBigInteger('arabia_worker_number')
                ->nullable()
                ->after('id')
                ->comment('رقم العامل الثابت في نظام أربيا');
            $table->unique('arabia_worker_number', 'maids_arabia_worker_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropUnique('maids_arabia_worker_number_unique');
            $table->dropColumn('arabia_worker_number');
        });
    }
};
