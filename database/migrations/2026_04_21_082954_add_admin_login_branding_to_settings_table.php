<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'admin_logo')) {
                $table->string('admin_logo')->nullable()->after('logo_header');
            }

            if (!Schema::hasColumn('settings', 'admin_login_background')) {
                $table->string('admin_login_background')->nullable()->after('cover_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'admin_logo')) {
                $table->dropColumn('admin_logo');
            }

            if (Schema::hasColumn('settings', 'admin_login_background')) {
                $table->dropColumn('admin_login_background');
            }
        });
    }
};
