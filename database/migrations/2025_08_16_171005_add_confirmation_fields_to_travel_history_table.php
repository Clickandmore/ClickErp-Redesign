<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('travel_histories', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('updated_at');
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            $table->timestamp('arrived_at')->nullable()->after('confirmed_by');
            $table->unsignedBigInteger('arrival_confirmed_by')->nullable()->after('arrived_at');

            // Add foreign keys
            $table->foreign('confirmed_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('arrival_confirmed_by')->references('id')->on('admins')->onDelete('set null');

            // Add indexes for better performance
            $table->index(['status', 'confirmed_at']);
            $table->index(['status', 'arrived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_histories', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropForeign(['arrival_confirmed_by']);
            $table->dropIndex(['status', 'confirmed_at']);
            $table->dropIndex(['status', 'arrived_at']);
            $table->dropColumn(['confirmed_at', 'confirmed_by', 'arrived_at', 'arrival_confirmed_by']);
        });
    }
};
