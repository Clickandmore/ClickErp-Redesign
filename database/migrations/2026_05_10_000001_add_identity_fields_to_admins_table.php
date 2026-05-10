<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'identity_number')) {
                $table->string('identity_number')->nullable()->after('verification_phone');
            }

            if (!Schema::hasColumn('admins', 'identity_expiry_date')) {
                $table->date('identity_expiry_date')->nullable()->after('identity_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'identity_expiry_date')) {
                $table->dropColumn('identity_expiry_date');
            }

            if (Schema::hasColumn('admins', 'identity_number')) {
                $table->dropColumn('identity_number');
            }
        });
    }
};
