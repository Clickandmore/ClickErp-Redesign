<?php

namespace App\Console\Commands;

use App\Services\ZohoSalesIq\ZohoSalesIqService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestZohoSalesIq extends Command
{
    protected $signature = 'zoho:test';
    protected $description = 'Test Zoho SalesIQ connection and OAuth token refresh';

    public function handle(ZohoSalesIqService $salesIq)
    {
        $this->info('Testing Zoho SalesIQ Configuration...');
        $this->line('');

        // Check if configured
        if (!$salesIq->isConfigured()) {
            $this->error('Missing configuration keys:');
            foreach ($salesIq->missingConfigKeys() as $key) {
                $this->error("  - $key");
            }
            return 1;
        }

        $this->info('✓ Configuration is complete');
        $this->line('');

        // Test OAuth token refresh
        $this->info('Testing OAuth Token Refresh...');
        
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post(
                    rtrim((string) config('services.zoho_salesiq.accounts_base_url'), '/') . '/oauth/v2/token',
                    [
                        'refresh_token' => config('services.zoho_salesiq.refresh_token'),
                        'client_id' => config('services.zoho_salesiq.client_id'),
                        'client_secret' => config('services.zoho_salesiq.client_secret'),
                        'grant_type' => 'refresh_token',
                    ]
                );

            $payload = $response->json();

            if ($response->successful() && isset($payload['access_token'])) {
                $this->info('✓ OAuth token refresh successful');
                $this->line('  Token: ' . substr($payload['access_token'], 0, 20) . '...');
                $this->line('  Expires in: ' . ($payload['expires_in'] ?? 'N/A') . ' seconds');
                return 0;
            } else {
                $this->error('✗ OAuth token refresh failed');
                $this->line('Status: ' . $response->status());
                $this->table(
                    ['Key', 'Value'],
                    collect($payload)->map(fn ($v, $k) => [$k, json_encode($v)])->values()->toArray()
                );
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('✗ Connection error: ' . $e->getMessage());
            return 1;
        }
    }
}
