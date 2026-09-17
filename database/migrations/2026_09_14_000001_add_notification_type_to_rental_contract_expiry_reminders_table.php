<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            $table->string('notification_type', 20)
                ->default('advance')
                ->after('channel');
            $table->index(
                ['rental_contract_id', 'expires_on', 'notification_type'],
                'rental_exp_rem_contract_date_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            $table->dropIndex('rental_exp_rem_contract_date_type_idx');
            $table->dropColumn('notification_type');
        });
    }
};
