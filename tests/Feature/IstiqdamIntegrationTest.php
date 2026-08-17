<?php

namespace Tests\Feature;

use App\Services\IstiqdasmService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class IstiqdamIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::forget('istiqdam_test_token');
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

    public function test_stage_alias_uses_the_existing_staging_configuration(): void
    {
        Config::set('istiqdam.current_env', 'stage');
        Config::set('istiqdam.environments.stage', null);
        Config::set('istiqdam.environments.staging', [
            'base_url' => 'https://gateway.test',
            'oauth_url' => 'https://oauth.test/token',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'api_key' => 'api-key',
            'scope' => 'IntegrationScope',
        ]);
        Config::set('istiqdam.token_cache_key', 'istiqdam_test_token');
        Config::set('istiqdam.logging.enabled', false);

        Http::fake([
            'https://oauth.test/token' => Http::response(['access_token' => 'alias-token']),
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

    private function configureEnvironment(string $environment, string $baseUrl): void
    {
        Config::set('istiqdam.current_env', $environment);
        Config::set("istiqdam.environments.{$environment}", [
            'base_url' => $baseUrl,
            'oauth_url' => 'https://oauth.test/token',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'api_key' => 'api-key',
            'scope' => 'IntegrationScope',
        ]);
        Config::set('istiqdam.token_cache_key', 'istiqdam_test_token');
        Config::set('istiqdam.logging.enabled', false);
        Cache::forget('istiqdam_test_token');
    }
}
