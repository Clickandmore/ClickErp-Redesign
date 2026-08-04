<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings') || Schema::hasColumn('settings', 'cv_public_site_url')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->string('cv_public_site_url', 500)
                ->nullable()
                ->after('cv_template_output_type');
        });

        $configuredUrl = rtrim(trim((string) config('app.public_site_url')), '/');
        if ($configuredUrl !== '') {
            DB::table('settings')
                ->whereNull('cv_public_site_url')
                ->update(['cv_public_site_url' => $configuredUrl]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'cv_public_site_url')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('cv_public_site_url');
            });
        }
    }
};
