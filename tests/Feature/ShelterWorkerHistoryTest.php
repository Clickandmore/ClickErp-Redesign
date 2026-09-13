<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Services\ingaz\ShelterWorkerHistoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShelterWorkerHistoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_history_combines_worker_contracts_and_counts_each_failed_request_once(): void
    {
        $maid = DB::table('maids')->insertGetId(['name' => 'History worker', 'created_at' => now()->subDays(10), 'updated_at' => now()]);
        $ids = [];
        foreach ([$maid, $maid, null] as $maidId) {
            $ids[] = DB::table('contracts')->insertGetId(['maid_id' => $maidId, 'type' => 'recruitment', 'status' => 'shelter', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ([[$ids[0], 'failed'], [$ids[1], 'failed'], [$ids[1], 'rejected'], [$ids[2], 'failed']] as [$id, $status]) {
            DB::table('sponsorship_transfers')->insert(['contract_id' => $id, 'status' => $status, 'failed_at' => $status === 'failed' ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
        }
        $history = app(ShelterWorkerHistoryService::class)->build(Contract::findOrFail($ids[0]));
        $this->assertEqualsCanonicalizing([$ids[0], $ids[1]], $history['contracts']->modelKeys());
        $this->assertSame(2, $history['failed_trials']);
        $this->assertCount(3, $history['transfers']);
        $this->assertSame('تسجيل العاملة في النظام', $history['events']->first()['title']);
        $this->assertSame(0, $history['open_complaints']);

        $unlinked = app(ShelterWorkerHistoryService::class)->build(Contract::findOrFail($ids[2]));
        $this->assertCount(1, $unlinked['contracts']);
        $this->assertCount(1, $unlinked['transfers']);
    }
}
