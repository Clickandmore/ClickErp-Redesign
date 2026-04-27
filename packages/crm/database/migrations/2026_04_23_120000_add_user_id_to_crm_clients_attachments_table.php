<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_clients_attachments', 'user_id')) {
            Schema::table('crm_clients_attachments', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_clients_attachments', 'user_id')) {
            Schema::table('crm_clients_attachments', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
