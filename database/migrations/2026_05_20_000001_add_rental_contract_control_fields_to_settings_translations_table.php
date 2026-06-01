<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('settings_translations', 'rental_contract_party_one_title')) {
                $table->string('rental_contract_party_one_title')->nullable()->after('rental_contract_intro_text');
            }
            if (!Schema::hasColumn('settings_translations', 'rental_contract_party_one_text')) {
                $table->text('rental_contract_party_one_text')->nullable()->after('rental_contract_party_one_title');
            }
            if (!Schema::hasColumn('settings_translations', 'rental_contract_party_two_title')) {
                $table->string('rental_contract_party_two_title')->nullable()->after('rental_contract_party_one_text');
            }
            if (!Schema::hasColumn('settings_translations', 'rental_contract_service_title')) {
                $table->string('rental_contract_service_title')->nullable()->after('rental_contract_party_two_title');
            }
            if (!Schema::hasColumn('settings_translations', 'rental_contract_sadad_label')) {
                $table->string('rental_contract_sadad_label')->nullable()->after('rental_contract_service_title');
            }
            if (!Schema::hasColumn('settings_translations', 'rental_contract_sadad_code')) {
                $table->string('rental_contract_sadad_code')->nullable()->after('rental_contract_sadad_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings_translations', function (Blueprint $table) {
            $columns = [
                'rental_contract_party_one_title',
                'rental_contract_party_one_text',
                'rental_contract_party_two_title',
                'rental_contract_service_title',
                'rental_contract_sadad_label',
                'rental_contract_sadad_code',
            ];

            $existingColumns = array_filter($columns, fn ($column) => Schema::hasColumn('settings_translations', $column));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
