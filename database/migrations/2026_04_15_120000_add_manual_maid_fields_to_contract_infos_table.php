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
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->string('manual_maid_name')->nullable()->after('tafwed_number');
            $table->string('manual_maid_passport_num', 100)->nullable()->after('manual_maid_name');
            $table->foreignId('manual_maid_nationality_id')
                ->nullable()
                ->after('manual_maid_passport_num')
                ->constrained('nationalities')
                ->nullOnDelete();
            $table->foreignId('manual_maid_office_id')
                ->nullable()
                ->after('manual_maid_nationality_id')
                ->constrained('recruitment_offices')
                ->nullOnDelete();
            $table->string('manual_maid_attachment')->nullable()->after('manual_maid_office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->dropForeign(['manual_maid_nationality_id']);
            $table->dropForeign(['manual_maid_office_id']);
            $table->dropColumn([
                'manual_maid_name',
                'manual_maid_passport_num',
                'manual_maid_nationality_id',
                'manual_maid_office_id',
                'manual_maid_attachment',
            ]);
        });
    }
};
