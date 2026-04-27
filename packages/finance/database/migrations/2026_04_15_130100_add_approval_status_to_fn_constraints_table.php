<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $approvedStatus = 'approved';

        Schema::table('fn_constraints', function (Blueprint $table) use ($approvedStatus) {
            if (!Schema::hasColumn('fn_constraints', 'approval_status')) {
                $table->string('approval_status')
                    ->default($approvedStatus)
                    ->after('created_by_id');
            }

            if (!Schema::hasColumn('fn_constraints', 'approved_at')) {
                $table->timestamp('approved_at')
                    ->nullable()
                    ->after('approval_status');
            }

            if (!Schema::hasColumn('fn_constraints', 'approved_by_id')) {
                $table->unsignedBigInteger('approved_by_id')
                    ->nullable()
                    ->after('approved_at');
                $table->foreign('approved_by_id')
                    ->references('id')
                    ->on('admins')
                    ->nullOnDelete();
            }
        });

        DB::table('fn_constraints')
            ->whereNull('approval_status')
            ->update(['approval_status' => $approvedStatus]);
    }

    public function down(): void
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            if (Schema::hasColumn('fn_constraints', 'approved_by_id')) {
                $table->dropForeign(['approved_by_id']);
                $table->dropColumn('approved_by_id');
            }

            if (Schema::hasColumn('fn_constraints', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('fn_constraints', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }
};
