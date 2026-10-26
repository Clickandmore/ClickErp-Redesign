<?php

namespace Tests\Feature;

use App\Models\SmsTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SmsTemplateCompanyNameTest extends TestCase
{
    use DatabaseTransactions;

    public function test_company_name_is_resolved_when_contract_alert_only_supplies_customer_variables(): void
    {
        $template = new SmsTemplate(['message' => 'مرحبًا {client_name}، تحيات {company_name}. {company_name}']);
        $name = (string) documentSetting('document_company_name', documentSetting('website_name', 'الشركة'));
        $message = $template->processMessage(['client_name' => 'عميل الاختبار']);
        $this->assertSame('مرحبًا عميل الاختبار، تحيات ' . $name . '. ' . $name, $message);
        $this->assertStringNotContainsString('{company_name}', $message);
    }

    public function test_explicit_company_name_is_preserved_and_other_variables_still_work(): void
    {
        $template = new SmsTemplate(['message' => '{company_name}: العقد {contract_number}']);
        $this->assertSame('شركة محددة: العقد RC-123', $template->processMessage([
            'company_name' => 'شركة محددة', 'contract_number' => 'RC-123',
        ]));
        $template->message = 'العقد {contract_number}';
        $this->assertSame('العقد RC-123', $template->processMessage(['contract_number' => 'RC-123']));
    }
}
