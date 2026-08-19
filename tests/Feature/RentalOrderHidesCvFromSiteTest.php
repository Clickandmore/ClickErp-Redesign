<?php

namespace Tests\Feature;

use App\Http\Actions\ingaz\MaidAction;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * السيرة المحجوزة بطلب قائم يجب أن تختفي من قوائم الموقع الخارجي،
 * وتعود للظهور فور رفض الطلب أو إلغائه.
 */
class RentalOrderHidesCvFromSiteTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function a_rental_order_removes_the_cv_from_the_external_listing(): void
    {
        $maid = $this->makeRentalMaid('Rental order hides cv');

        $this->assertTrue($this->listedIds()->contains($maid->id));

        $order = $this->makeRentalOrder($maid, 'new');

        $this->assertFalse(
            $this->listedIds()->contains($maid->id),
            'السيرة ما زالت ظاهرة على الموقع الخارجي رغم وجود طلب إيجار قائم.'
        );

        $order->update(['status' => 'refused']);

        $this->assertTrue(
            $this->listedIds()->contains($maid->id),
            'السيرة لم تعد للظهور بعد رفض الطلب.'
        );
    }

    /** @test */
    public function an_order_under_process_also_keeps_the_cv_hidden(): void
    {
        $maid = $this->makeRentalMaid('Rental order under process');
        $this->makeRentalOrder($maid, 'under_process');

        $this->assertFalse($this->listedIds()->contains($maid->id));
    }

    private function listedIds()
    {
        $request = Request::create('/client/all-workers', 'GET', [
            'type' => 'egar',
            'per_page' => 100,
        ]);

        return app(MaidAction::class)->paginate($request)->getCollection()->pluck('id');
    }

    private function makeRentalOrder(Maid $maid, string $status): Order
    {
        return Order::query()->create([
            'user_id' => $this->makeClient(),
            'maid_id' => $maid->id,
            'job_type_id' => 2,
            'status' => $status,
            'payment_method' => 'cash',
            'order_code' => 'RENT-' . random_int(100000, 999999),
        ]);
    }

    private function makeRentalMaid(string $name): Maid
    {
        DB::table('job_types')->updateOrInsert(
            ['id' => 2],
            ['image' => '', 'created_at' => now(), 'updated_at' => now()]
        );

        $nationalityId = DB::table('nationalities')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $religionId = DB::table('religions')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $maidId = DB::table('maids')->insertGetId([
            'name' => $name,
            'passport_num' => 'RO-' . str_replace('.', '', uniqid('', true)),
            'nationality_id' => $nationalityId,
            'religion_id' => $religionId,
            'age' => 30,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'available',
            'available' => true,
            'is_show' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('maid_jobs')->insert([
            'maid_id' => $maidId,
            'job_type_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Maid::query()->findOrFail($maidId);
    }

    private function makeClient(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Rental order client',
            'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
