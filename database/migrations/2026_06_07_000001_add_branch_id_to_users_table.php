<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $column = $table->unsignedBigInteger('branch_id')->nullable();

                if (Schema::hasColumn('users', 'created_by')) {
                    $column->after('created_by');
                }

                $table->index('branch_id', 'users_branch_id_index');

                if (Schema::hasTable('branches')) {
                    $table->foreign('branch_id', 'users_branch_id_foreign')
                        ->references('id')
                        ->on('branches')
                        ->nullOnDelete();
                }
            });
        }

        $this->ensureMainBranch();
        $this->backfillMainBranch();
    }

    public function down()
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'branch_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasTable('branches')) {
                try {
                    $table->dropForeign('users_branch_id_foreign');
                } catch (\Throwable $e) {
                    //
                }
            }

            try {
                $table->dropIndex('users_branch_id_index');
            } catch (\Throwable $e) {
                //
            }

            $table->dropColumn('branch_id');
        });
    }

    private function backfillMainBranch(): void
    {
        if (
            !Schema::hasTable('branches') ||
            !Schema::hasColumn('users', 'branch_id') ||
            !Schema::hasColumn('branches', 'is_main')
        ) {
            return;
        }

        $branchId = DB::table('branches')
            ->where('is_active', true)
            ->where('is_main', true)
            ->value('id');

        if (!$branchId) {
            $branchId = DB::table('branches')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name_ar')
                ->value('id');
        }

        if (!$branchId) {
            return;
        }

        DB::table('users')
            ->whereNull('branch_id')
            ->update(['branch_id' => $branchId]);
    }

    private function ensureMainBranch(): void
    {
        if (
            !Schema::hasTable('branches') ||
            !Schema::hasColumn('branches', 'is_main')
        ) {
            return;
        }

        $existingMainBranchId = DB::table('branches')
            ->where('is_main', true)
            ->value('id');

        if ($existingMainBranchId) {
            DB::table('branches')
                ->where('id', $existingMainBranchId)
                ->update($this->onlyExistingBranchColumns([
                    'is_active' => true,
                    'updated_at' => now(),
                ]));

            return;
        }

        DB::table('branches')->insert($this->onlyExistingBranchColumns([
            'name_ar' => 'الفرع الرئيسي',
            'name_en' => 'Main Branch',
            'code' => $this->uniqueBranchCode('MAIN'),
            'is_active' => true,
            'is_main' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function onlyExistingBranchColumns(array $payload): array
    {
        $columns = array_flip(Schema::getColumnListing('branches'));

        return array_intersect_key($payload, $columns);
    }

    private function uniqueBranchCode(string $baseCode): string
    {
        if (!Schema::hasColumn('branches', 'code')) {
            return $baseCode;
        }

        $code = $baseCode;
        $counter = 2;

        while (DB::table('branches')->where('code', $code)->exists()) {
            $code = $baseCode . '-' . $counter++;
        }

        return $code;
    }
}
