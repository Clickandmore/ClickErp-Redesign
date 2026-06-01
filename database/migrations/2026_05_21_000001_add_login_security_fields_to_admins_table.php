<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('admins', 'current_session_id')) {
                $table->string('current_session_id', 120)->nullable()->after('remember_token');
            }

            if (! Schema::hasColumn('admins', 'current_session_ip')) {
                $table->string('current_session_ip', 45)->nullable()->after('current_session_id');
            }

            if (! Schema::hasColumn('admins', 'current_session_user_agent')) {
                $table->text('current_session_user_agent')->nullable()->after('current_session_ip');
            }

            if (! Schema::hasColumn('admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('current_session_user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            foreach ([
                'last_login_at',
                'current_session_user_agent',
                'current_session_ip',
                'current_session_id',
                'username',
            ] as $column) {
                if (Schema::hasColumn('admins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
