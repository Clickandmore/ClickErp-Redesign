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
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->enum('closure_type', ['available', 'transferred'])->nullable()->after('status');
            $table->timestamp('closed_at')->nullable()->after('closure_type');
            $table->unsignedBigInteger('transferred_to_contract_id')->nullable()->after('closed_at');

            // Add index for better performance
            $table->index(['status', 'closure_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropIndex(['status', 'closure_type']);
            $table->dropColumn(['closure_type', 'closed_at', 'transferred_to_contract_id']);
        });
    }
};
