<?php

namespace Tests\Unit;

use App\Models\ingaz\Setting;
use App\Models\WebsiteVisitorSite;
use Tests\TestCase;

class WebsiteVisitorTrackingTest extends TestCase
{
    public function test_site_accepts_only_its_explicit_domains(): void
    {
        $site = new WebsiteVisitorSite([
            'allowed_domains' => ['example.com', '*.client.example'],
        ]);

        $this->assertTrue($site->allowsOrigin('https://example.com/page'));
        $this->assertTrue($site->allowsOrigin('https://shop.client.example/page'));
        $this->assertTrue($site->allowsOrigin('https://client.example'));
        $this->assertFalse($site->allowsOrigin('https://not-example.com/page'));
        $this->assertFalse($site->allowsOrigin(null));
    }

    public function test_settings_expose_the_selected_active_tracking_code(): void
    {
        $site = new WebsiteVisitorSite([
            'public_key' => str_repeat('a', 48),
            'allowed_domains' => ['client.example'],
            'is_active' => true,
        ]);
        $site->id = 10;

        $settings = new Setting();
        $settings->setRelation('websiteVisitorSite', $site);

        $payload = $settings->visitorTrackingPayload();

        $this->assertTrue($payload['enabled']);
        $this->assertSame(['client.example'], $payload['allowed_domains']);
        $this->assertStringContainsString('/visitor-widget/' . str_repeat('a', 48) . '.js', $payload['script_url']);
        $this->assertStringContainsString('<script async src=', $payload['embed_code']);
    }

    public function test_settings_do_not_expose_an_inactive_tracking_code(): void
    {
        $site = new WebsiteVisitorSite([
            'public_key' => str_repeat('b', 48),
            'allowed_domains' => ['client.example'],
            'is_active' => false,
        ]);

        $settings = new Setting();
        $settings->setRelation('websiteVisitorSite', $site);

        $payload = $settings->visitorTrackingPayload();

        $this->assertFalse($payload['enabled']);
        $this->assertNull($payload['script_url']);
        $this->assertNull($payload['embed_code']);
    }
}
