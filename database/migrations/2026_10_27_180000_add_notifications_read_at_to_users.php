<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Customer notifications newer than this time are unread; general notifications have no user row, so read state lives on the user.
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'notifications_read_at')) return;
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('notifications_read_at')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'notifications_read_at')) return;
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notifications_read_at'));
    }
};
