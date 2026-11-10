<?php
namespace Tests\Unit;

use App\Services\WhatsApp\ClickChatTemplateStatus;
use PHPUnit\Framework\TestCase;

class ClickChatTemplateStatusTest extends TestCase
{
    private array $binding = ['name' => 'contract_update', 'language' => 'ar'];

    public function test_pending_and_rejected_are_distinguished_from_missing(): void
    {
        foreach (['APPROVED', 'PENDING', 'REJECTED', 'PAUSED', 'DISABLED'] as $state) {
            $badge = ClickChatTemplateStatus::describe($this->binding, ['success' => true, 'templates' => [
                ['name' => 'contract_update', 'language' => 'ar', 'status' => $state, 'rejection_reason' => 'Example rejection reason'],
            ]]);
            $this->assertSame($state, $badge['state']);
            if ($state === 'REJECTED') $this->assertSame('Example rejection reason', $badge['detail']);
        }
        $this->assertSame('missing', ClickChatTemplateStatus::describe($this->binding, ['success' => true, 'templates' => []])['state']);
    }

    public function test_outage_and_old_api_are_not_treated_as_rejection_or_absence(): void
    {
        foreach (['unavailable', 'api_outdated', 'other_provider', 'not_configured', 'sender_required'] as $error) {
            $badge = ClickChatTemplateStatus::describe($this->binding, ['success' => false, 'error' => $error]);
            $this->assertSame($error, $badge['state']);
            $this->assertSame('secondary', $badge['color']);
        }
    }

    public function test_media_template_is_explained_instead_of_silently_disappearing(): void
    {
        $notice = ClickChatTemplateStatus::pickerNotice(['success' => true, 'templates' => [
            ['name' => 'special_offer', 'status' => 'APPROVED', 'sendable_by_api' => false],
        ]], []);
        $this->assertStringContainsString('special_offer', $notice);
        $this->assertStringContainsString('صورة', $notice);
        $this->assertNull(ClickChatTemplateStatus::pickerNotice(['success' => true, 'templates' => []], [['name' => 'text_update']]));
    }

    public function test_permission_errors_and_old_api_have_actionable_messages(): void
    {
        $this->assertStringContainsString('messages:write', ClickChatTemplateStatus::pickerNotice(['success' => false, 'error' => 'forbidden'], []));
        $this->assertSame('يلزم تحديث ClickChat لقراءة الحالة', ClickChatTemplateStatus::describe($this->binding, ['success' => false, 'error' => 'api_outdated'])['label']);
    }

    public function test_a_local_template_is_not_claimed_to_have_been_submitted(): void
    {
        $this->assertSame('unbound', ClickChatTemplateStatus::describe(null, ['success' => true, 'templates' => []])['state']);
        $this->assertSame('missing', ClickChatTemplateStatus::describe($this->binding, ['success' => true, 'templates' => [
            ['name' => 'contract_update', 'language' => 'en', 'status' => 'APPROVED'],
        ]])['state']);
    }
}
