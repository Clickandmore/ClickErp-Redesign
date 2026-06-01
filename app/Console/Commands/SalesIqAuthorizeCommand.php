<?php

namespace App\Console\Commands;

use App\Services\Zoho\SalesIqClient;
use Illuminate\Console\Command;

class SalesIqAuthorizeCommand extends Command
{
    protected $signature = 'salesiq:authorize
                            {--code= : Authorization code returned from Zoho callback}
                            {--debug-token : Show masked Zoho token/config diagnostics}
                            {--test-refresh : Test current refresh token and show Zoho response}';

    protected $description = 'Generate Zoho SalesIQ OAuth authorization URL or exchange an authorization code for tokens';

    public function handle(SalesIqClient $salesIq): int
    {
        if ($this->option('debug-token')) {
            foreach (['client_id', 'client_secret', 'refresh_token', 'screenname', 'redirect_uri'] as $key) {
                $value = (string) config("services.zoho_salesiq.{$key}");
                $this->line(sprintf(
                    '%s: len=%d prefix=%s suffix=%s',
                    $key,
                    strlen($value),
                    substr($value, 0, 12),
                    substr($value, -8)
                ));
            }

            $refreshToken = (string) config('services.zoho_salesiq.refresh_token');
            $parts = explode('.', $refreshToken);
            $this->line('refresh_token_parts: ' . count($parts));
            $this->line('expected_refresh_token_shape: 1000.xxxxx.yyyyy');

            return self::SUCCESS;
        }

        if ($this->option('test-refresh')) {
            try {
                $token = $salesIq->accessToken();
                $this->info('Refresh token is valid. Access token received.');
                $this->line('access_token: ' . substr($token, 0, 10) . '...' . substr($token, -8));

                return self::SUCCESS;
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        $code = trim((string) $this->option('code'));

        if ($code !== '') {
            $tokens = $salesIq->exchangeCodeForTokens($code);

            $this->info('Token exchange completed.');

            if (!empty($tokens['refresh_token'])) {
                $this->line('Add this value to .env as ZOHO_SALESIQ_REFRESH_TOKEN:');
                $this->line($tokens['refresh_token']);
            } else {
                $this->warn('Zoho did not return a refresh_token. Re-run authorization with prompt=consent or revoke the old grant.');
            }

            if (!empty($tokens['access_token'])) {
                $this->line('Access token received for immediate testing.');
            }

            return self::SUCCESS;
        }

        $url = $salesIq->authorizationUrl(SalesIqClient::DEFAULT_SCOPES, csrf_token());

        $this->info('Open this Zoho authorization URL in the browser:');
        $this->line($url);
        $this->newLine();
        $this->line('After approving access, copy the code parameter from the redirect URL and run:');
        $this->line('php artisan salesiq:authorize --code=PASTE_CODE_HERE');

        return self::SUCCESS;
    }
}
