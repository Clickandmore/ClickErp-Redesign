<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->string('submission_type', 20)
                ->nullable()
                ->after('submission_status')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropIndex(['submission_type']);
            $table->dropColumn('submission_type');
        });
    }
};
