<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\SmsTemplate;
use App\Models\ingaz\User;
use App\Support\RentalExtensionMessage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RentalExtensionMessageTemplateTest extends TestCase
{
    use DatabaseTransactions;

    private function contract(): RentalContract
    {
        $contract = new RentalContract(['id'=>360, 'contract_number'=>'RC-2026-0360', 'user_id'=>1]);
        $contract->setRelation('user', new User(['name'=>'أحمد', 'phone'=>'0501234567', 'email'=>'test@example.test']));
        $contract->setRelation('info', new RentalContractInfo(['ingaz_to'=>'2026-11-10']));
        return $contract;
    }

    private function send($controller): array
    {
        $method = new \ReflectionMethod(RentContractController::class, 'sendRentalExtensionMessageToClient');
        $method->setAccessible(true);
        return $method->invoke($controller, $this->contract());
    }

    public function test_edited_template_is_rendered_with_the_extension_date_and_sent(): void
    {
        SmsTemplate::updateOrCreate(['name'=>RentalExtensionMessage::TEMPLATE_NAME], [
            'title'=>'رسالة تمديد عقد التأجير', 'channel'=>'sms', 'is_active'=>true,
            'message'=>'أهلًا {client_name}: العقد {contract_number} ينتهي {new_end_date}',
        ]);
        $controller = \Mockery::mock(RentContractController::class)->makePartial();
        $controller->shouldReceive('sendSMS')->once()->withArgs(fn($phone,$message) =>
            $message === 'أهلًا أحمد: العقد RC-2026-0360 ينتهي 2026-11-10'
        )->andReturn(['success'=>true]);
        $this->assertTrue($this->send($controller)['success']);
    }

    public function test_disabled_template_does_not_send_any_message(): void
    {
        SmsTemplate::updateOrCreate(['name'=>RentalExtensionMessage::TEMPLATE_NAME], [
            'title'=>'رسالة تمديد', 'message'=>'تمديد {contract_number}', 'is_active'=>false,
        ]);
        $controller = \Mockery::mock(RentContractController::class)->makePartial();
        $controller->shouldNotReceive('sendSMS');
        $this->assertSame('skipped', $this->send($controller)['status']);
    }

    public function test_missing_template_uses_the_original_text(): void
    {
        SmsTemplate::where('name',RentalExtensionMessage::TEMPLATE_NAME)->delete();
        $controller = \Mockery::mock(RentContractController::class)->makePartial();
        $controller->shouldReceive('sendSMS')->once()->withArgs(fn($phone,$message) =>
            $message === "عميلنا أحمد،\nتم تمديد عقد الإيجار رقم RC-2026-0360 حتى تاريخ 2026-11-10.\nشكراً لتعاملكم معنا."
        )->andReturn(['success'=>true]);
        $this->assertTrue($this->send($controller)['success']);
    }

    public function test_installing_the_template_preserves_existing_custom_text(): void
    {
        SmsTemplate::where('name',RentalExtensionMessage::TEMPLATE_NAME)->delete();
        $migration = require base_path('database/migrations/2026_10_15_150000_add_rental_extension_sms_template.php');
        $migration->up();
        $template = SmsTemplate::where('name',RentalExtensionMessage::TEMPLATE_NAME)->firstOrFail();
        $this->assertSame(RentalExtensionMessage::VARIABLES,$template->variables);
        $template->update(['message'=>'نص مخصص', 'is_active'=>false]);
        $migration->up();
        $this->assertSame('نص مخصص',$template->fresh()->message);
        $this->assertFalse($template->fresh()->is_active);
    }
}
