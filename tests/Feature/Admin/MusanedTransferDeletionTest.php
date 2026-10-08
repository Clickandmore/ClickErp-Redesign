<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Models\MusanedTransfer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\ConstraintService;
use Tests\TestCase;

class MusanedTransferDeletionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_deleting_a_transfer_soft_deletes_both_linked_constraints(): void
    {
        $now = now();
        $constraintIds = [];

        foreach (['قيد تسوية تحويل مساند', 'قيد التحويل البنكي لمساند'] as $statement) {
            $constraintIds[] = DB::table('fn_constraints')->insertGetId([
                'code' => (int) DB::table('fn_constraints')->max('code') + 1,
                'type' => 1,
                'value' => 100,
                'date' => $now->toDateString(),
                'statement' => $statement,
                'year_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $transferId = DB::table('musaned_transfers')->insertGetId([
            'reference_number' => 'TEST-DELETE',
            'transfer_date' => $now->toDateString(),
            'settlement_constraint_id' => $constraintIds[0],
            'bank_transfer_constraint_id' => $constraintIds[1],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = app(MusanedTransferController::class)->destroy(
            $transferId,
            app(ConstraintService::class)
        );

        $this->assertNull(MusanedTransfer::query()->find($transferId));
        $this->assertDatabaseHas('fn_constraints', [
            'id' => $constraintIds[0],
        ]);
        $this->assertDatabaseHas('fn_constraints', [
            'id' => $constraintIds[1],
        ]);
        $this->assertNotNull(DB::table('fn_constraints')->where('id', $constraintIds[0])->value('deleted_at'));
        $this->assertNotNull(DB::table('fn_constraints')->where('id', $constraintIds[1])->value('deleted_at'));
        $this->assertSame(302, $response->getStatusCode());
    }
}
