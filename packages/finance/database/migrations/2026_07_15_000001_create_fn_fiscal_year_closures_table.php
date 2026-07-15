<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fn_fiscal_year_closures', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('operation_number')->nullable()->unique();
            $table->string('title', 500);
            $table->decimal('income_balance', 18, 2)->default(0);
            $table->enum('income_side', ['debtor', 'creditor'])->default('debtor');
            $table->date('closing_date');
            $table->unsignedBigInteger('constraint_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_constraint_code')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by_id')->nullable();
            $table->string('reversed_by_name')->nullable();
            $table->string('source', 32)->default('system');
            $table->timestamps();

            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->nullOnDelete();
            $table->foreign('created_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('reversed_by_id')->references('id')->on('admins')->nullOnDelete();
        });

        $adminId = DB::table('admins')->where('id', 1)->value('id');
        $now = now();

        $history = [
            ['operation_number' => 7, 'title' => 'إقفال مالي حتي 31-12-2020', 'income_balance' => 2451978.97, 'income_side' => 'creditor', 'closing_date' => '2020-12-31', 'legacy_constraint_code' => 0, 'constraint_lookup_code' => 5334, 'created_at' => '2022-10-02', 'reversed_at' => null],
            ['operation_number' => 8, 'title' => 'اقفال مالى لعام 2021 م', 'income_balance' => 413704.43, 'income_side' => 'debtor', 'closing_date' => '2021-12-31', 'legacy_constraint_code' => 21798, 'constraint_lookup_code' => null, 'created_at' => '2026-02-17', 'reversed_at' => '2026-02-17'],
            ['operation_number' => 9, 'title' => 'اقفال مالى لعام 2021 م', 'income_balance' => 413704.43, 'income_side' => 'debtor', 'closing_date' => '2021-12-31', 'legacy_constraint_code' => 21801, 'constraint_lookup_code' => null, 'created_at' => '2026-02-17', 'reversed_at' => '2026-02-17'],
            ['operation_number' => 10, 'title' => 'اقفال مالى لعام 2021 م', 'income_balance' => 413704.43, 'income_side' => 'debtor', 'closing_date' => '2021-12-31', 'legacy_constraint_code' => 21805, 'constraint_lookup_code' => 21805, 'created_at' => '2026-02-17', 'reversed_at' => null],
            ['operation_number' => 11, 'title' => 'اقفال مالى لعام 2022 م', 'income_balance' => 306885.09, 'income_side' => 'creditor', 'closing_date' => '2022-12-31', 'legacy_constraint_code' => 21806, 'constraint_lookup_code' => null, 'created_at' => '2026-02-17', 'reversed_at' => '2026-02-22'],
            ['operation_number' => 12, 'title' => 'اقفال مالى لعام 2023 م', 'income_balance' => 582723.24, 'income_side' => 'debtor', 'closing_date' => '2023-12-31', 'legacy_constraint_code' => 21807, 'constraint_lookup_code' => null, 'created_at' => '2026-02-17', 'reversed_at' => '2026-02-22'],
            ['operation_number' => 13, 'title' => 'اقفال مالى لعام 2024 م', 'income_balance' => 122922.08, 'income_side' => 'creditor', 'closing_date' => '2024-12-31', 'legacy_constraint_code' => 21808, 'constraint_lookup_code' => null, 'created_at' => '2026-02-17', 'reversed_at' => '2026-02-22'],
            ['operation_number' => 14, 'title' => 'اقفال مالى لعام 2022م', 'income_balance' => 306885.09, 'income_side' => 'creditor', 'closing_date' => '2022-12-31', 'legacy_constraint_code' => 21894, 'constraint_lookup_code' => 21894, 'created_at' => '2026-02-22', 'reversed_at' => null],
            ['operation_number' => 15, 'title' => 'اقفال مالى لعام 2023 م', 'income_balance' => 582723.24, 'income_side' => 'debtor', 'closing_date' => '2023-12-31', 'legacy_constraint_code' => 21895, 'constraint_lookup_code' => 21895, 'created_at' => '2026-02-22', 'reversed_at' => null],
            ['operation_number' => 16, 'title' => 'اقفال مالى لعام 2024 م', 'income_balance' => 227253.61, 'income_side' => 'creditor', 'closing_date' => '2024-12-31', 'legacy_constraint_code' => 21896, 'constraint_lookup_code' => null, 'created_at' => '2026-02-22', 'reversed_at' => '2026-02-26'],
            ['operation_number' => 17, 'title' => 'اقفال مالى لعام 2024م', 'income_balance' => 227253.61, 'income_side' => 'creditor', 'closing_date' => '2024-12-31', 'legacy_constraint_code' => 21951, 'constraint_lookup_code' => null, 'created_at' => '2026-02-26', 'reversed_at' => '2026-02-27'],
            ['operation_number' => 18, 'title' => 'اقفال مالى لعام 2024 م', 'income_balance' => 0, 'income_side' => 'creditor', 'closing_date' => '2024-12-31', 'legacy_constraint_code' => 21952, 'constraint_lookup_code' => null, 'created_at' => '2026-02-27', 'reversed_at' => '2026-02-27'],
        ];

        foreach ($history as $row) {
            $constraintId = $row['constraint_lookup_code']
                ? DB::table('fn_constraints')
                    ->where('code', $row['constraint_lookup_code'])
                    ->whereNull('deleted_at')
                    ->value('id')
                : null;

            DB::table('fn_fiscal_year_closures')->insert([
                'operation_number' => $row['operation_number'],
                'title' => $row['title'],
                'income_balance' => $row['income_balance'],
                'income_side' => $row['income_side'],
                'closing_date' => $row['closing_date'],
                'constraint_id' => $constraintId,
                'legacy_constraint_code' => $row['legacy_constraint_code'],
                'created_by_id' => $adminId,
                'created_by_name' => 'System Admin',
                'reversed_at' => $row['reversed_at'],
                'reversed_by_id' => $row['reversed_at'] ? $adminId : null,
                'reversed_by_name' => $row['reversed_at'] ? 'System Admin' : null,
                'source' => 'legacy',
                'created_at' => $row['created_at'],
                'updated_at' => $row['reversed_at'] ?: $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fn_fiscal_year_closures');
    }
};
