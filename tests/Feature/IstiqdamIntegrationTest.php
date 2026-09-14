<?php

namespace Tests\Feature;

use App\Services\IstiqdasmService;
use App\Models\IstiqdamSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class IstiqdamIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_staging_connection_uses_takamol_gateway_and_unwrapped_response_is_available(): void
    {
        $this->configureEnvironment('staging', 'https://gateway.test');

        Http::fake([
            'https://oauth.test/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]),
            'https://gateway.test/takamol/staging/api/v1/external/packages*' => Http::response([
                'body' => [
                    'response' => [
                        'content' => [['id' => 101]],
                        'total' => 1,
                    ],
                ],
            ]),
        ]);

        $result = app(IstiqdasmService::class)->getPackages(0, 10, ['gender' => 'female']);

        $this->assertTrue($result['success']);
        $this->assertSame(101, $result['data']['body']['response']['content'][0]['id']);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/packages?')) {
                return false;
            }

            return str_contains($request->url(), 'Size=10')
                && str_contains($request->url(), 'Page=0')
                && str_contains($request->url(), 'Gender=female')
                && $request->hasHeader('Authorization', 'Bearer test-access-token')
                && $request->hasHeader('X-IBM-Client-Secret', 'client-secret');
        });
    }

    public function test_staging_contract_creation_uses_documented_v2_gateway_endpoint(): void
    {
        $this->configureEnvironment('staging', 'https://gateway.test');

        Http::fake([
            'https://oauth.test/token' => Http::response(['access_token' => 'test-access-token']),
            'https://gateway.test/takamol/staging/external/v2/createcontractapiwrapper' => Http::response([
                'body' => ['response' => ['number' => '12345']],
            ]),
        ]);

        $result = app(IstiqdasmService::class)->createContract(['package' => ['id' => 1]]);

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => $request->url() === 'https://gateway.test/takamol/staging/external/v2/createcontractapiwrapper');
    }

    public function test_production_uses_protected_istiqdam_endpoint(): void
    {
        $this->configureEnvironment('production', 'https://production.test');

        Http::fake([
            'https://oauth.test/token' => Http::response([
                'access_token' => 'production-token',
                'expires_in' => 3600,
            ]),
            'https://production.test/api/protected/istiqdam/packages*' => Http::response([
                'content' => [],
                'totalElements' => 0,
            ]),
        ]);

        $result = app(IstiqdasmService::class)->getPackages(0, 5);

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            '/api/protected/istiqdam/packages?page=0&size=5'
        ));
    }

    public function test_database_setting_is_the_only_source_of_environment_configuration(): void
    {
        $this->configureEnvironment('staging', 'https://gateway.test');
        Config::set('istiqdam.current_env', 'production');
        Config::set('istiqdam.environments.production.base_url', 'https://must-not-be-used.test');

        Http::fake([
            'https://oauth.test/token' => Http::response(['access_token' => 'database-token']),
            'https://gateway.test/takamol/staging/api/v1/external/packages*' => Http::response([
                'body' => ['response' => ['content' => [], 'total' => 0]],
            ]),
        ]);

        $this->assertTrue(app(IstiqdasmService::class)->getPackages(0, 1)['success']);
    }

    public function test_real_contract_form_renders_with_platform_packages(): void
    {
        $html = view('admin.istiqdam.real-create-contract', [
            'packages' => [[
                'id' => 77,
                'occupation' => ['name' => ['ar' => 'عاملة منزلية']],
                'nationality' => ['name' => ['ar' => 'الفلبين']],
                'price' => ['gross' => 15000],
            ]],
            'totalRecords' => 1,
            'apiEnvironment' => 'staging',
        ])->render();

        $this->assertStringContainsString('إنشاء عقد استقدام حقيقي', $html);
        $this->assertStringContainsString('value="77"', $html);
        $this->assertStringContainsString(route('istiqdam.contracts.store'), $html);
    }

    public function test_real_contract_create_route_is_registered_before_dynamic_contract_route(): void
    {
        $createRoute = Route::getRoutes()->getByName('istiqdam.contracts.create');
        $showRoute = Route::getRoutes()->getByName('istiqdam.contracts.show');

        $this->assertNotNull($createRoute);
        $this->assertNotNull($showRoute);
        $this->assertStringEndsWith('admin/istiqdam/contracts/create', $createRoute->uri());
        $this->assertStringEndsWith('admin/istiqdam/contracts/{contractId}', $showRoute->uri());
    }

    public function test_optional_api_key_header_is_only_sent_when_configured(): void
    {
        $this->configureEnvironment('staging', 'https://gateway.test');
        IstiqdamSetting::query()->firstOrFail()->update(['api_key' => null]);

        Http::fake([
            'https://oauth.test/token' => Http::response(['access_token' => 'token-without-api-key']),
            'https://gateway.test/takamol/staging/api/v1/external/packages*' => Http::response([
                'body' => ['response' => ['content' => [], 'total' => 0]],
            ]),
        ]);

        $this->assertTrue(app(IstiqdasmService::class)->getPackages(0, 1)['success']);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'oauth.test')) {
                return false;
            }

            return $request->hasHeader('X-IBM-Client-Id', 'client-id')
                && $request->hasHeader('X-IBM-Client-Secret', 'client-secret')
                && !$request->hasHeader('API-KEY');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/packages?') && !$request->hasHeader('API-KEY');
        });
    }

    public function test_failed_connection_test_exposes_the_gateway_own_rejection(): void
    {
        $this->configureEnvironment('staging', 'https://gateway.test');

        Http::fake([
            'https://oauth.test/token' => Http::response([
                'httpCode' => '401',
                'httpMessage' => 'Unauthorized',
                'moreInformation' => 'Client ID or Client Secret is invalid',
            ], 401, ['X-Global-Transaction-ID' => 'trace-12345']),
        ]);

        $result = app(IstiqdasmService::class)->testConnection();

        $this->assertFalse($result['success']);
        $this->assertSame('oauth', $result['provider']['stage']);
        $this->assertSame(401, $result['provider']['http_status']);
        $this->assertSame('Client ID or Client Secret is invalid', $result['provider']['provider_message']);
        $this->assertStringContainsString('Unauthorized', $result['provider']['provider_body']);
        $this->assertSame('trace-12345', $result['provider']['gateway_trace']['X-Global-Transaction-ID']);
    }

    public function test_network_failure_is_reported_with_the_raw_curl_error(): void
    {
        $this->configureEnvironment('staging', 'https://gateway.test');

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException(
                'cURL error 28: Failed to connect to gateway.test port 443 after 10000 ms'
            );
        });

        $result = app(IstiqdasmService::class)->testConnection();

        $this->assertFalse($result['success']);
        $this->assertSame('network', $result['provider']['stage']);
        $this->assertStringContainsString('cURL error 28', $result['provider']['raw_error']);
    }

    private function configureEnvironment(string $environment, string $baseUrl): void
    {
        IstiqdamSetting::query()->updateOrCreate(['id' => 1], [
            'environment' => $environment,
            'base_url' => $baseUrl,
            'oauth_url' => 'https://oauth.test/token',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'api_key' => 'api-key',
            'scope' => 'IntegrationScope',
            'timeout' => 30,
            'connect_timeout' => 10,
            'token_cache_ttl' => 3500,
            'logging_enabled' => false,
        ]);
        Cache::flush();
        $this->app->forgetInstance(IstiqdasmService::class);
    }
}
