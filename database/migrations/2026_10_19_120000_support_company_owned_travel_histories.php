<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('company_profile_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (DB::table('travel_histories')->whereNull('user_id')->exists()
            || DB::table('travel_histories')->whereNotNull('company_profile_id')->exists()) {
            throw new RuntimeException('Cannot remove company travel support while company trips exist.');
        }

        Schema::table('travel_histories', function (Blueprint $table) {
            $table->dropColumn('company_profile_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
