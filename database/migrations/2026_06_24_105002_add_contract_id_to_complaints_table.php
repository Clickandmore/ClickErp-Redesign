<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'contract_id')) {
                $table->foreignId('contract_id')
                    ->nullable()
                    ->after('complaint_type_id')
                    ->constrained('contracts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
        });
    }
};
