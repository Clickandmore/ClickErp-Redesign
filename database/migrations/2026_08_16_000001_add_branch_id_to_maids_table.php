<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maids') || Schema::hasColumn('maids', 'branch_id')) {
            return;
        }

        Schema::table('maids', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('office_id');
            $table->index('branch_id', 'maids_branch_id_index');
            $table->foreign('branch_id', 'maids_branch_id_foreign')
                ->references('id')->on('branches')->nullOnDelete();
        });

        $this->backfillRentalMaidBranches();
    }

    public function down(): void
    {
        if (!Schema::hasTable('maids') || !Schema::hasColumn('maids', 'branch_id')) {
            return;
        }

        Schema::table('maids', function (Blueprint $table) {
            $table->dropForeign('maids_branch_id_foreign');
            $table->dropIndex('maids_branch_id_index');
            $table->dropColumn('branch_id');
        });
    }

    private function backfillRentalMaidBranches(): void
    {
        if (!Schema::hasTable('maid_jobs') || !Schema::hasTable('branches')) {
            return;
        }

        $rentalTypeId = DB::table('job_types_translations')
            ->where(function ($query) {
                $query->where('title', 'like', '%إيجار%')
                    ->orWhere('title', 'like', '%ايجار%')
                    ->orWhere('title', 'like', '%تأجير%')
                    ->orWhere('title', 'like', '%rent%');
            })
            ->value('job_type_id') ?: 2;

        $mainBranchId = DB::table('branches')->where('is_main', true)->value('id')
            ?: DB::table('branches')->where('is_active', true)->orderBy('sort_order')->value('id');

        DB::table('maid_jobs')
            ->where('job_type_id', $rentalTypeId)
            ->orderBy('maid_id')
            ->pluck('maid_id')
            ->unique()
            ->chunk(250)
            ->each(function ($maidIds) use ($mainBranchId) {
                foreach ($maidIds as $maidId) {
                    $branchId = null;

                    if (Schema::hasTable('rental_contracts') && Schema::hasColumn('rental_contracts', 'branch_id')) {
                        $branchId = DB::table('rental_contracts')
                            ->where('maid_id', $maidId)->whereNotNull('branch_id')
                            ->latest('id')->value('branch_id');
                    }

                    if (!$branchId && Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id')) {
                        $branchId = DB::table('orders')
                            ->where('maid_id', $maidId)->whereNotNull('branch_id')
                            ->latest('id')->value('branch_id');
                    }

                    if (!$branchId && Schema::hasColumn('maids', 'added_by') && Schema::hasColumn('admins', 'branch_id')) {
                        $addedBy = DB::table('maids')->where('id', $maidId)->value('added_by');
                        $branchId = $addedBy ? DB::table('admins')->where('id', $addedBy)->value('branch_id') : null;
                    }

                    DB::table('maids')->where('id', $maidId)->update([
                        'branch_id' => $branchId ?: $mainBranchId,
                    ]);
                }
            });
    }
};
