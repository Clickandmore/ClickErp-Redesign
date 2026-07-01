<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'against_name')) {
                $table->string('against_name')->nullable()->after('subject');
            }

            if (!Schema::hasColumn('complaints', 'last_response')) {
                $table->text('last_response')->nullable()->after('response');
            }

            if (!Schema::hasColumn('complaints', 'submission_method')) {
                $table->string('submission_method')->nullable()->after('last_response');
            }

            if (!Schema::hasColumn('complaints', 'related_to')) {
                $table->string('related_to')->nullable()->after('submission_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            foreach (['related_to', 'submission_method', 'last_response', 'against_name'] as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
