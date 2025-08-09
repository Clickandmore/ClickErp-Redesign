<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            // Using try-catch to avoid errors if indexes already exist
            try {
                $table->index('status', 'orders_status_index');
            } catch (\Exception $e) {}

            try {
                $table->index('created_at', 'orders_created_at_index');
            } catch (\Exception $e) {}

            try {
                $table->index('user_id', 'orders_user_id_index');
            } catch (\Exception $e) {}

            try {
                $table->index('maid_id', 'orders_maid_id_index');
            } catch (\Exception $e) {}

            try {
                $table->index('job_type_id', 'orders_job_type_id_index');
            } catch (\Exception $e) {}

            try {
                $table->index('deadline_at', 'orders_deadline_at_index');
            } catch (\Exception $e) {}

            try {
                $table->index('order_code', 'orders_order_code_index');
            } catch (\Exception $e) {}

            // Composite index for status and created_at (commonly used together)
            try {
                $table->index(['status', 'created_at'], 'orders_status_created_at_index');
            } catch (\Exception $e) {}
        });

        // Add indexes for order_details table
        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                try {
                    $table->index('order_id', 'order_details_order_id_index');
                } catch (\Exception $e) {}

                try {
                    $table->index('admin_id', 'order_details_admin_id_index');
                } catch (\Exception $e) {}
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['maid_id']);
            $table->dropIndex(['job_type_id']);
            $table->dropIndex(['deadline_at']);
            $table->dropIndex(['order_code']);
            $table->dropIndex(['status', 'created_at']);
        });

        if (Schema::hasTable('order_details')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->dropIndex(['order_id']);
                $table->dropIndex(['admin_id']);
            });
        }
    }
};
