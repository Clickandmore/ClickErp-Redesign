<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ingaz_delegations')) {
            return;
        }

        Schema::table('ingaz_delegations', function (Blueprint $table) {
            if (! Schema::hasColumn('ingaz_delegations', 'commissioned_at')) {
                $table->timestamp('commissioned_at')->nullable()->after('updated_at');
            }

            if (! Schema::hasColumn('ingaz_delegations', 'commission_batch_id')) {
                $table->unsignedBigInteger('commission_batch_id')->nullable()->after('commissioned_at');
            }
        });

        if ($this->commissionBatchForeignKey() === null) {
            Schema::table('ingaz_delegations', function (Blueprint $table) {
                $table->foreign('commission_batch_id')
                    ->references('id')
                    ->on('commission_batches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ingaz_delegations')) {
            return;
        }

        $foreignKey = $this->commissionBatchForeignKey();

        Schema::table('ingaz_delegations', function (Blueprint $table) use ($foreignKey) {
            if ($foreignKey !== null) {
                $table->dropForeign($foreignKey);
            }

            $columns = array_values(array_filter(
                ['commissioned_at', 'commission_batch_id'],
                fn (string $column) => Schema::hasColumn('ingaz_delegations', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function commissionBatchForeignKey(): ?string
    {
        $foreignKey = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [DB::getDatabaseName(), 'ingaz_delegations', 'commission_batch_id']
        );

        return $foreignKey ? data_get($foreignKey, 'CONSTRAINT_NAME') : null;
    }
};
