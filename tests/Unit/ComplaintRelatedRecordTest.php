<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\Contract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\Order;
use App\Models\RentalContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Tests\TestCase;

class ComplaintRelatedRecordTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_resolves_an_imported_legacy_recruitment_order_by_its_code(): void
    {
        // مراجع الاستيراد تحمل رقم الطلب (ARB-761) لا معرّف الصف
        $order = $this->createOrder('ARB-761');

        $record = $this->resolve(new Complaint([
            'related_to' => 'طلب استقدام 761',
        ]));

        $this->assertSame('order', $record['type']);
        $this->assertSame('طلب استقدام', $record['label']);
        $this->assertSame('ARB-761', $record['reference']);
        $this->assertStringEndsWith('/admin/orders/' . $order->id, $record['url']);
    }

    public function test_the_arb_code_wins_over_an_order_whose_code_is_the_bare_number(): void
    {
        $bareCodeOrder = $this->createOrder('761');
        $arbOrder = $this->createOrder('ARB-761');

        $record = $this->resolve(new Complaint([
            'related_to' => 'طلب استقدام 761',
        ]));

        $this->assertStringEndsWith('/admin/orders/' . $arbOrder->id, $record['url']);
        $this->assertStringNotContainsString('/admin/orders/' . $bareCodeOrder->id, $record['url']);
    }

    public function test_an_unresolvable_legacy_reference_keeps_its_text_without_a_dead_link(): void
    {
        $record = $this->resolve(new Complaint([
            'related_to' => 'طلب استقدام 999999999',
        ]));

        $this->assertSame('999999999', $record['reference']);
        $this->assertNull($record['url']);
    }

    public function test_it_resolves_an_imported_legacy_rental_contract(): void
    {
        $rentalContract = RentalContract::query()->create([
            'user_id' => 1,
            'contract_number' => '1332',
            'status' => 'new',
        ]);

        $record = $this->resolve(new Complaint([
            'related_to' => 'طلب تأجير 1332',
        ]));

        $this->assertSame('rental_contract', $record['type']);
        $this->assertSame('عقد تأجير', $record['label']);
        $this->assertStringEndsWith('/admin/RentContract/' . $rentalContract->id, $record['url']);
    }

    public function test_it_resolves_a_current_order_relation(): void
    {
        $order = new Order(['order_code' => 'ARB-2420']);
        $order->id = 6752;

        $complaint = new Complaint(['relation_type' => 'order']);
        $complaint->setRelation('order', $order);

        $record = $this->resolve($complaint);

        $this->assertSame('order', $record['type']);
        $this->assertSame('طلب استقدام', $record['label']);
        $this->assertSame('ARB-2420', $record['reference']);
        $this->assertStringEndsWith('/admin/orders/6752', $record['url']);
    }

    public function test_it_distinguishes_shelter_and_recruitment_contracts(): void
    {
        $shelterContract = new Contract(['status' => 'shelter']);
        $shelterContract->id = 7635;
        $shelterComplaint = new Complaint(['relation_type' => 'contract']);
        $shelterComplaint->setRelation('contract', $shelterContract);

        $recruitmentContract = new Contract(['status' => 'completed']);
        $recruitmentContract->id = 7313;
        $recruitmentComplaint = new Complaint(['relation_type' => 'contract']);
        $recruitmentComplaint->setRelation('contract', $recruitmentContract);

        $shelterRecord = $this->resolve($shelterComplaint);
        $recruitmentRecord = $this->resolve($recruitmentComplaint);

        $this->assertSame('shelter_contract', $shelterRecord['type']);
        $this->assertSame('عقد إيواء', $shelterRecord['label']);
        $this->assertStringEndsWith('/admin/ShelterContract/7635', $shelterRecord['url']);
        $this->assertSame('contract', $recruitmentRecord['type']);
        $this->assertSame('عقد استقدام', $recruitmentRecord['label']);
        $this->assertStringEndsWith('/admin/contractsDetails/7313', $recruitmentRecord['url']);
    }

    /**
     * كل نوع ارتباط يحمل أيقونته — الألوان مبنية على المفتاح type في الواجهات.
     */
    public function test_every_record_carries_a_type_and_an_icon(): void
    {
        $complaint = new Complaint(['relation_type' => 'manual', 'relation_reference' => 'خطاب رقم 12']);

        $record = $this->resolve($complaint);

        $this->assertSame('manual', $record['type']);
        $this->assertSame('fa-pen', $record['icon']);
        $this->assertSame('خطاب رقم 12', $record['reference']);
    }

    private function createOrder(string $orderCode): Order
    {
        $order = new Order(['order_code' => $orderCode]);
        $order->user_id = 1;
        $order->job_type_id = 1;
        $order->save();

        return $order;
    }

    private function resolve(Complaint $complaint): ?array
    {
        $method = new ReflectionMethod(ComplaintController::class, 'resolveComplaintRelatedRecord');

        return $method->invoke(app(ComplaintController::class), $complaint);
    }
}
