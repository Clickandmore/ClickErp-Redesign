<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_infos', 'shelter_quick_tags')) {
                $table->json('shelter_quick_tags')
                    ->nullable()
                    ->comment('تصنيفات سريعة لعقود الإيواء');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            if (Schema::hasColumn('contract_infos', 'shelter_quick_tags')) {
                $table->dropColumn('shelter_quick_tags');
            }
        });
    }
};
