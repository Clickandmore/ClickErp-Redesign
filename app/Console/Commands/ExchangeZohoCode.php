<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ExchangeZohoCode extends Command
{
    protected $signature = 'zoho:exchange-code {code} {--client-id=} {--client-secret=}';
    protected $description = 'Exchange Zoho authorization code for refresh token';

    public function handle()
    {
        $code = $this->argument('code');
        $clientId = $this->option('client-id') ?: config('services.zoho_salesiq.client_id');
        $clientSecret = $this->option('client-secret') ?: config('services.zoho_salesiq.client_secret');
        $accountsBaseUrl = rtrim((string) config('services.zoho_salesiq.accounts_base_url'), '/');

        if (!$code || !$clientId || !$clientSecret) {
            $this->error('Missing required parameters:');
            if (!$code) $this->error('  - code (pass as argument)');
            if (!$clientId) $this->error('  - client-id (in config or --client-id)');
            if (!$clientSecret) $this->error('  - client-secret (in config or --client-secret)');
            return 1;
        }

        $this->info('Exchanging Zoho authorization code...');
        $this->line('');

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post("$accountsBaseUrl/oauth/v2/token", [
                    'grant_type' => 'authorization_code',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'redirect_uri' => 'http://localhost',
                ]);

            $payload = $response->json();

            if ($response->successful() && isset($payload['refresh_token'])) {
                $this->info('✓ Successfully exchanged code for tokens!');
                $this->line('');
                $this->table(['Parameter', 'Value'], [
                    ['Access Token', substr($payload['access_token'], 0, 20) . '...'],
                    ['Refresh Token', $payload['refresh_token']],
                    ['Expires In', $payload['expires_in'] ?? 'N/A'],
                    ['Token Type', $payload['token_type'] ?? 'Bearer'],
                ]);
                $this->line('');
                $this->warn('Update your .env file with:');
                $this->line('');
                $this->getOutput()->writeln("<fg=yellow>ZOHO_SALESIQ_REFRESH_TOKEN=" . $payload['refresh_token'] . "</>");
                $this->line('');
                $this->info('Then run: php artisan config:clear');
                return 0;
            } else {
                $this->error('✗ Failed to exchange code');
                $this->line('Status: ' . $response->status());
                $this->line('');
                $this->table(['Key', 'Value'], collect($payload)->map(fn ($v, $k) => [$k, json_encode($v)])->values()->toArray());
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('✗ Connection error: ' . $e->getMessage());
            return 1;
        }
    }
}
