<?php

namespace Tests\Unit;

use App\Services\WhatsApp\MetaWhatsAppTemplateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWhatsAppTemplateServiceTest extends TestCase
{
    private MetaWhatsAppTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.whatsapp.graph_version' => 'v23.0',
            'services.whatsapp.business_account_id' => 'waba-123',
            'services.whatsapp.access_token' => 'meta-token',
            'services.whatsapp.timeout' => 15,
        ]);
        $this->service = app(MetaWhatsAppTemplateService::class);
    }

    public function test_it_lists_and_filters_approved_templates(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => '1',
                        'name' => 'customer_follow_up',
                        'language' => 'ar',
                        'status' => 'APPROVED',
                        'category' => 'UTILITY',
                        'components' => [[
                            'type' => 'BODY',
                            'text' => 'مرحبًا {{1}} بخصوص طلب {{2}}',
                        ]],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->listTemplates();
        $template = $this->service->approvedTemplate('customer_follow_up', 'ar');

        $this->assertTrue($result['success']);
        $this->assertSame('customer_follow_up', $template['name']);
        $this->assertSame(2, $this->service->bodyVariableCount($template));
    }

    public function test_it_creates_a_template_with_examples_and_quick_replies(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => 'template-123',
                'status' => 'PENDING',
                'category' => 'UTILITY',
            ], 200),
        ]);

        $result = $this->service->create([
            'name' => 'customer_follow_up',
            'language' => 'ar',
            'category' => 'UTILITY',
            'header' => 'متابعة الطلب',
            'body' => 'مرحبًا {{1}} بخصوص طلب {{2}}',
            'footer' => 'Click ERP',
            'examples' => ['محمد', 'ORD-1001'],
            'quick_replies' => ['مهتم', 'لاحقًا'],
        ]);

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://graph.facebook.com/v23.0/waba-123/message_templates'
                && data_get($request->data(), 'components.1.example.body_text.0.1') === 'ORD-1001'
                && data_get($request->data(), 'components.3.buttons.0.type') === 'QUICK_REPLY';
        });
    }
}
