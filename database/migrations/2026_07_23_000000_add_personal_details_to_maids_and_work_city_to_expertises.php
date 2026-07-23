<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('passport_num');
            $table->string('relative_name')->nullable()->after('phone');
            $table->string('relative_phone', 50)->nullable()->after('relative_name');
            $table->string('residence_city')->nullable()->after('relative_phone');
            $table->string('passport_issue_place')->nullable()->after('passport_issue_date');
            $table->boolean('has_driving_license')->nullable()->after('education');
        });

        Schema::table('maid_expertises', function (Blueprint $table) {
            $table->string('work_city')->nullable()->after('country_id');
        });
    }

    public function down(): void
    {
        Schema::table('maid_expertises', function (Blueprint $table) {
            $table->dropColumn('work_city');
        });

        Schema::table('maids', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'relative_name',
                'relative_phone',
                'residence_city',
                'passport_issue_place',
                'has_driving_license',
            ]);
        });
    }
};
