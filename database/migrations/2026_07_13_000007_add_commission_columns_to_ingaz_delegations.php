<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingaz_delegations', function (Blueprint $table) {
            $table->timestamp('commissioned_at')->nullable()->after('updated_at');
            $table->unsignedBigInteger('commission_batch_id')->nullable()->after('commissioned_at');

            $table->foreign('commission_batch_id')->references('id')->on('commission_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ingaz_delegations', function (Blueprint $table) {
            $table->dropForeign(['commission_batch_id']);
            $table->dropColumn(['commissioned_at', 'commission_batch_id']);
        });
    }
};
