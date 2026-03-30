<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('admins', 'verification_phone')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('verification_phone')->nullable()->after('whatsapp');
            });
        }

        DB::table('admins')
            ->where(function ($query) {
                $query->whereNull('verification_phone')
                    ->orWhere('verification_phone', '');
            })
            ->update([
                'verification_phone' => DB::raw('phone'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('admins', 'verification_phone')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('verification_phone');
            });
        }
    }
};
