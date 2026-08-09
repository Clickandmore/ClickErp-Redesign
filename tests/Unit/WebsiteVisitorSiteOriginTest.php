<?php

namespace Tests\Unit;

use App\Models\WebsiteVisitorSite;
use PHPUnit\Framework\TestCase;

class WebsiteVisitorSiteOriginTest extends TestCase
{
    public function test_it_only_allows_explicit_domains_and_their_configured_subdomains(): void
    {
        $site = new WebsiteVisitorSite([
            'allowed_domains' => ['example.com', '*.example.org'],
        ]);

        $this->assertTrue($site->allowsOrigin('https://example.com/page'));
        $this->assertTrue($site->allowsOrigin('https://store.example.org/page'));
        $this->assertFalse($site->allowsOrigin('https://attacker.test/page'));
    }

    public function test_open_wildcard_does_not_allow_arbitrary_origins(): void
    {
        $site = new WebsiteVisitorSite([
            'allowed_domains' => ['*'],
        ]);

        $this->assertFalse($site->allowsOrigin('https://attacker.test/page'));
    }
}
