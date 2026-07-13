<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'relation_type')) {
                $table->string('relation_type')->nullable()->after('complaint_type_id')->index();
            }
            if (!Schema::hasColumn('complaints', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('relation_type')->constrained('orders')->nullOnDelete();
            }
            if (!Schema::hasColumn('complaints', 'related_user_id')) {
                $table->foreignId('related_user_id')->nullable()->after('contract_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('complaints', 'recruitment_office_id')) {
                $table->foreignId('recruitment_office_id')->nullable()->after('related_user_id')->constrained('recruitment_offices')->nullOnDelete();
            }
            if (!Schema::hasColumn('complaints', 'relation_reference')) {
                $table->string('relation_reference')->nullable()->after('recruitment_office_id');
            }
            if (!Schema::hasColumn('complaints', 'relation_notes')) {
                $table->text('relation_notes')->nullable()->after('relation_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            foreach (['recruitment_office_id', 'related_user_id', 'order_id'] as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropForeign([$column]);
                }
            }

            $columns = array_values(array_filter(
                ['relation_notes', 'relation_reference', 'recruitment_office_id', 'related_user_id', 'order_id', 'relation_type'],
                fn (string $column) => Schema::hasColumn('complaints', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
