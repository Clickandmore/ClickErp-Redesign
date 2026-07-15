<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_settings')) {
            $this->reconcileExistingTable();

            return;
        }

        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->string('service_type'); // ingaz, delegation, transfer, rental
            $table->enum('method', ['percentage', 'fixed']);
            $table->decimal('value', 10, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique('service_type');
            $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }

    private function reconcileExistingTable(): void
    {
        $requiredColumns = [
            'id',
            'service_type',
            'method',
            'value',
            'created_by',
            'created_at',
            'updated_at',
        ];

        $missingColumns = array_values(array_filter(
            $requiredColumns,
            fn (string $column) => ! Schema::hasColumn('commission_settings', $column)
        ));

        if ($missingColumns !== []) {
            throw new \RuntimeException(
                'Existing commission_settings table has an incompatible schema. Missing columns: '
                . implode(', ', $missingColumns)
            );
        }

        if (! $this->hasUniqueServiceTypeIndex()) {
            Schema::table('commission_settings', function (Blueprint $table) {
                $table->unique('service_type');
            });
        }

        if (! $this->hasCreatedByForeignKey()) {
            Schema::table('commission_settings', function (Blueprint $table) {
                $table->foreign('created_by')
                    ->references('id')
                    ->on('admins')
                    ->nullOnDelete();
            });
        }
    }

    private function hasUniqueServiceTypeIndex(): bool
    {
        return DB::selectOne(
            'SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND NON_UNIQUE = 0
             LIMIT 1',
            [DB::getDatabaseName(), 'commission_settings', 'service_type']
        ) !== null;
    }

    private function hasCreatedByForeignKey(): bool
    {
        return DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?
             LIMIT 1',
            [DB::getDatabaseName(), 'commission_settings', 'created_by', 'admins']
        ) !== null;
    }
};
