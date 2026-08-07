<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_blocked', 'users_is_blocked_index');
            $table->index('client_type', 'users_client_type_index');
            $table->index('created_at', 'users_created_at_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'orders_user_created_index');
            $table->index(['user_id', 'status'], 'orders_user_status_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['user_id', 'type'], 'contracts_user_type_index');
        });

        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'rental_contracts_user_created_index');
        });

        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->index(
                ['old_sponsor_id', 'status', 'contract_id'],
                'sponsor_transfers_old_status_contract_index'
            );
            $table->index(
                ['old_sponsor_id', 'created_at'],
                'sponsor_transfers_old_created_index'
            );
            $table->index(
                ['new_sponsor_id', 'created_at'],
                'sponsor_transfers_new_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropIndex('sponsor_transfers_old_status_contract_index');
            $table->dropIndex('sponsor_transfers_old_created_index');
            $table->dropIndex('sponsor_transfers_new_created_index');
        });

        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropIndex('rental_contracts_user_created_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_user_type_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_created_index');
            $table->dropIndex('orders_user_status_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_blocked_index');
            $table->dropIndex('users_client_type_index');
            $table->dropIndex('users_created_at_index');
        });
    }
};
