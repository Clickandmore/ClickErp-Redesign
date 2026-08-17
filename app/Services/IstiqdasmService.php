<?php

namespace App\Services;

use App\Models\IstiqdamSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class IstiqdasmService
{
    protected string $baseUrl;
    protected string $oauthUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $apiKey;
    protected string $scope;
    protected string $environment;
    protected int $timeout;
    protected int $connectTimeout;
    protected string $tokenCacheKey;
    protected int $tokenCacheTtl;
    protected ?string $lastAuthenticationError = null;

    public function __construct()
    {
        $env    = Config::get('istiqdam.current_env', 'staging');
        $config = Config::get("istiqdam.environments.{$env}");

        // The working Asuss installation calls this environment "stage" while
        // this project historically used "staging". Accept both names.
        if (!is_array($config) && $env === 'stage') {
            $config = Config::get('istiqdam.environments.staging');
        }

        if (!is_array($config)) {
            throw new \RuntimeException("Istiqdam environment [{$env}] is not configured.");
        }

        // Keep the integration usable during a staged deployment before the
        // settings migration has been applied; environment values remain the fallback.
        try {
            $storedSettings = IstiqdamSetting::query()->first();
        } catch (\Throwable $exception) {
            $storedSettings = null;
        }
        if ($storedSettings) {
            $config['client_id'] = $storedSettings->client_id ?: $config['client_id'];
            $config['client_secret'] = $storedSettings->client_secret ?: $config['client_secret'];
            $config['api_key'] = $storedSettings->api_key ?: $config['api_key'];
        }

        $this->environment   = $env;
        $this->baseUrl       = rtrim($config['base_url'], '/');
        $this->oauthUrl      = $config['oauth_url'];
        $this->clientId      = $config['client_id'];
        $this->clientSecret  = $config['client_secret'];
        $this->apiKey        = $config['api_key'];
        $this->scope         = $config['scope'];
        $this->timeout       = Config::get('istiqdam.timeout', 30);
        $this->connectTimeout = Config::get('istiqdam.connect_timeout', 10);
        $this->tokenCacheKey = Config::get('istiqdam.token_cache_key', 'istiqdam_oauth_token');
        $this->tokenCacheTtl = Config::get('istiqdam.token_cache_ttl', 3500);
    }

    // -------------------------------------------------------------------------
    // OAuth2 Authentication
    // -------------------------------------------------------------------------

    /**
     * Get a valid Bearer token (from cache or fetch a new one).
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember($this->tokenCacheKey, $this->tokenCacheTtl, function () {
            return $this->fetchNewToken();
        });
    }

    /**
     * Force-refresh the token (clears cache then fetches).
     */
    public function refreshToken(): ?string
    {
        Cache::forget($this->tokenCacheKey);
        return $this->getAccessToken();
    }

    /**
     * Call the OAuth2 /token endpoint and return only the access_token string.
     */
    private function fetchNewToken(): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-IBM-Client-Id'     => $this->clientId,
                    'X-IBM-Client-Secret' => $this->clientSecret,
                    'API-KEY'             => $this->apiKey,
                    'Content-Type'        => 'application/x-www-form-urlencoded',
                ])
                ->withOptions([
                    'verify' => true,
                    'http_errors' => false,
                    'connect_timeout' => $this->connectTimeout,
                ])
                ->asForm()
                ->post($this->oauthUrl, [
                    'grant_type' => 'client_credentials',
                    'scope'      => $this->scope,
                ]);

            // OAuth responses contain the access token, so never pass the response body to the API logger.
            if (Config::get('istiqdam.logging.enabled')) {
                Log::channel('istiqdam')->info('Istiqdam OAuth2 token request', [
                    'endpoint' => $this->oauthUrl,
                    'status' => $response->status(),
                ]);
            }

            if ($response->successful()) {
                $token = $response->json('access_token');
                $this->lastAuthenticationError = $token
                    ? null
                    : 'استجابة OAuth لا تحتوي على access_token.';

                return $token;
            }

            $responseBody = $response->json();
            $providerMessage = is_array($responseBody)
                ? ($responseBody['error_description'] ?? $responseBody['description'] ?? $responseBody['message'] ?? $responseBody['error'] ?? null)
                : null;
            $this->lastAuthenticationError = 'رفضت بوابة OAuth الطلب (HTTP ' . $response->status() . ')' . ($providerMessage ? ': ' . $providerMessage : '.');

            Log::channel('istiqdam')->error('Istiqdam OAuth2 token fetch failed', [
                'status'   => $response->status(),
                'response' => $responseBody,
            ]);

            return null;

        } catch (\Exception $e) {
            $this->lastAuthenticationError = $this->connectionErrorMessage($e);
            Log::channel('istiqdam')->error('Istiqdam OAuth2 exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // HTTP client
    // -------------------------------------------------------------------------

    /**
     * Build HTTP client with OAuth2 Bearer token + required headers.
     */
    private function getHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $token = $this->getAccessToken();

        return Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-IBM-Client-Id' => $this->clientId,
                'X-IBM-Client-Secret' => $this->clientSecret,
                'API-KEY'         => $this->apiKey,
                'Accept'          => 'application/json',
                'Content-Type'    => 'application/json',
            ])
            ->withOptions([
                'verify' => true,
                'http_errors' => false,
                'connect_timeout' => $this->connectTimeout,
            ]);
    }

    // -------------------------------------------------------------------------
    // Logging
    // -------------------------------------------------------------------------

    private function logApiCall(string $method, string $endpoint, $requestData = null, $response = null, $error = null): void
    {
        if (!Config::get('istiqdam.logging.enabled')) {
            return;
        }

        $logData = [
            'method'          => $method,
            'endpoint'        => $endpoint,
            'request_data'    => $requestData,
            'response_status' => $response ? $response->status() : null,
            'response_data'   => $response ? $response->json() : null,
            'error'           => $error,
            'timestamp'       => now(),
        ];

        if ($error) {
            Log::channel('istiqdam')->error('Istiqdam API Error', $logData);
        } else {
            Log::channel('istiqdam')->info('Istiqdam API Call', $logData);
        }
    }

    // -------------------------------------------------------------------------
    // API Methods
    // -------------------------------------------------------------------------

    /**
     * Create Contract
     * POST /takamol/staging/external/v2/createcontractapiwrapper
     */
    public function createContract(array $contractData): array
    {
        $endpoint = $this->endpoint(
            '/takamol/staging/external/v2/createcontractapiwrapper',
            '/api/protected/istiqdam/contract'
        );
        $url      = $this->baseUrl . $endpoint;

        try {
            $response = $this->getHttpClient()->post($url, $contractData);
            $this->logApiCall('POST', $endpoint, $contractData, $response);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return $this->errorResponse($response, 'فشل في إنشاء العقد');

        } catch (\Exception $e) {
            $this->logApiCall('POST', $endpoint, $contractData, null, $e->getMessage());
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    /**
     * Get Contract Details
     * GET /takamol/staging/api/v1/external/contractDetails/{ContractId}
     */
    public function getContractDetails(string $contractId): array
    {
        $endpoint = $this->endpoint(
            "/takamol/staging/api/v1/external/contractDetails/{$contractId}",
            "/api/protected/istiqdam/contractDetails/{$contractId}"
        );
        $url      = $this->baseUrl . $endpoint;

        try {
            $response = $this->getHttpClient()->get($url);
            $this->logApiCall('GET', $endpoint, null, $response);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return $this->errorResponse($response, 'فشل في جلب تفاصيل العقد');

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint, null, null, $e->getMessage());
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel Contract
     * GET /takamol/staging/api/v1/external/cancelContract/{ContractId}
     */
    public function cancelContract(string $contractId): array
    {
        $endpoint = $this->endpoint(
            "/takamol/staging/api/v1/external/cancelContract/{$contractId}",
            "/api/protected/istiqdam/cancelContract/{$contractId}"
        );
        $url      = $this->baseUrl . $endpoint;

        try {
            $response = $this->getHttpClient()->get($url);
            $this->logApiCall('GET', $endpoint, null, $response);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return $this->errorResponse($response, 'فشل في إلغاء العقد');

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint, null, null, $e->getMessage());
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    /**
     * Download Contract
     * GET /takamol/staging/api/v1/external/downloadContract/{contractId}?type=pdf
     */
    public function downloadContract(string $contractId, string $type = 'pdf'): array
    {
        $endpoint = $this->endpoint(
            "/takamol/staging/api/v1/external/downloadContract/{$contractId}",
            "/api/protected/istiqdam/downloadContract/{$contractId}"
        );
        $url      = $this->baseUrl . $endpoint . "?type={$type}";

        try {
            $response = $this->getHttpClient()
                ->withHeaders(['Content-Type' => 'application/pdf'])
                ->get($url);

            $this->logApiCall('GET', $endpoint, ['type' => $type], $response);

            if ($response->successful()) {
                return [
                    'success'      => true,
                    'data'         => $response->body(),
                    'content_type' => $response->header('Content-Type'),
                ];
            }

            return $this->errorResponse($response, 'فشل في تحميل العقد');

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint, ['type' => $type], null, $e->getMessage());
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    /**
     * Get Single Package
     * GET /takamol/staging/api/v1/external/package/{id}
     */
    public function getPackage(int $packageId): array
    {
        $endpoint = $this->endpoint(
            "/takamol/staging/api/v1/external/package/{$packageId}",
            "/api/protected/istiqdam/package/{$packageId}"
        );
        $url      = $this->baseUrl . $endpoint;

        try {
            $response = $this->getHttpClient()->get($url);
            $this->logApiCall('GET', $endpoint, null, $response);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return $this->errorResponse($response, 'فشل في جلب تفاصيل الباقة');

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint, null, null, $e->getMessage());
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    /**
     * Get Packages (paginated + filterable)
     * GET /takamol/staging/api/v1/external/packages
     *
     * @param int   $page    Zero-based page number
     * @param int   $size    Page size
     * @param array $filters Optional filters (nationality, occupation, gender, priceGrossMin, priceGrossMax …)
     */
    public function getPackages(int $page = 0, int $size = 10, array $filters = []): array
    {
        $endpoint = $this->endpoint(
            '/takamol/staging/api/v1/external/packages',
            '/api/protected/istiqdam/packages'
        );

        $queryParams = $this->usesTakamolGateway()
            ? array_merge([
                'Page' => $page,
                'Size' => $size,
                'Sort' => 'createdAt,desc',
            ], $this->mapGatewayFilters($filters))
            : array_merge([
                'page' => $page,
                'size' => $size,
                'sort' => 'createdAt,desc',
            ], $filters);

        $url = $this->baseUrl . $endpoint . '?' . http_build_query($queryParams);

        try {
            $response = $this->getHttpClient()->get($url);
            $this->logApiCall('GET', $endpoint, $queryParams, $response);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return $this->errorResponse($response, 'فشل في جلب الباقات');

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint, $queryParams, null, $e->getMessage());
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    /**
     * Work Permit Assignment
     * POST /takamol/staging/api/v1/external/workPermitAssignment/{contractId}
     *
     * @param string $contractId   Istiqdam contract number
     * @param array  $workPermitData  { "number": "...", "laborer": { "id": "...", "birthdate": "..." } }
     */
    public function assignWorkPermit(string $contractId, array $workPermitData): array
    {
        $endpoint = $this->endpoint(
            "/takamol/staging/api/v1/external/workPermitAssignment/{$contractId}",
            "/api/protected/istiqdam/workPermitAssignment/{$contractId}"
        );
        $url      = $this->baseUrl . $endpoint;

        try {
            $response = $this->getHttpClient()->post($url, $workPermitData);
            $this->logApiCall('POST', $endpoint, $workPermitData, $response);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return $this->errorResponse($response, 'فشل في إضافة العاملة للعقد');

        } catch (\Exception $e) {
            $this->logApiCall('POST', $endpoint, $workPermitData, null, $e->getMessage());
            return ['success' => false, 'error' => 'خطأ في الاتصال: ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Diagnostics
    // -------------------------------------------------------------------------

    /**
     * Quick connectivity test (fetches token then calls packages endpoint).
     */
    public function testConnection(): array
    {
        // Step 1: token
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error'   => $this->lastAuthenticationError
                    ?: 'فشل الحصول على OAuth2 Token؛ تحقق من Client ID وClient Secret وAPI Key.',
            ];
        }

        // Step 2: packages endpoint
        $result = $this->getPackages(0, 1);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'تم الاتصال بمنصة الاستقدام بنجاح',
                'data'    => $result['data'],
            ];
        }

        return [
            'success' => false,
            'error'   => $result['error'] ?? 'خطأ غير معروف',
            'details' => $result,
        ];
    }

    /**
     * Full connection diagnosis.
     */
    public function diagnoseConnection(): array
    {
        $env = Config::get('istiqdam.current_env');

        $diagnosis = [
            'timestamp'         => now()->toISOString(),
            'environment'       => $env,
            'base_url'          => $this->baseUrl,
            'oauth_url'         => $this->oauthUrl,
            'client_id'         => $this->clientId,
            'client_secret_set' => !empty($this->clientSecret),
            'api_key_length'    => strlen($this->apiKey),
            'scope'             => $this->scope,
            'timeout'           => $this->timeout,
        ];

        // Token test
        try {
            $token = $this->fetchNewToken();
            $diagnosis['oauth2_test'] = [
                'success'    => (bool) $token,
            ];
        } catch (\Exception $e) {
            $diagnosis['oauth2_test'] = ['error' => $e->getMessage()];
        }

        // Packages endpoint test
        try {
            $result = $this->getPackages(0, 1);
            $diagnosis['packages_test'] = [
                'success'  => $result['success'],
                'response' => $result['data'] ?? ($result['error'] ?? null),
            ];
        } catch (\Exception $e) {
            $diagnosis['packages_test'] = ['error' => $e->getMessage()];
        }

        return $diagnosis;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function errorResponse($response, string $defaultMsg): array
    {
        $body = $response->json();

        // Try to refresh token on 401 (expired)
        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey);
        }

        return [
            'success'     => false,
            'error'       => $body['description'] ?? ($body['message'] ?? $defaultMsg),
            'status_code' => $response->status(),
            'body'        => $body,
        ];
    }

    private function usesTakamolGateway(): bool
    {
        return in_array($this->environment, ['stage', 'staging', 'uat'], true);
    }

    private function endpoint(string $gatewayEndpoint, string $productionEndpoint): string
    {
        return $this->usesTakamolGateway() ? $gatewayEndpoint : $productionEndpoint;
    }

    private function mapGatewayFilters(array $filters): array
    {
        $keyMap = [
            'packageid' => 'PackageId',
            'duration' => 'Duration',
            'nationality' => 'Nationality',
            'occupation' => 'Occupation',
            'gender' => 'Gender',
            'pricegrossmin' => 'PriceGrossMin',
            'pricegrossmax' => 'PriceGrossMax',
        ];

        $mapped = [];
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $mapped[$keyMap[strtolower((string) $key)] ?? $key] = $value;
        }

        return $mapped;
    }

    private function connectionErrorMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (preg_match('/cURL error 6|Could not resolve host/i', $message)) {
            return 'تعذر العثور على نطاق بوابة تكامل (DNS).';
        }

        if (preg_match('/cURL error 7|Failed to connect/i', $message)) {
            return 'تعذر فتح اتصال شبكي مع بوابة تكامل؛ تحقق من الجدار الناري وIP المسموح.';
        }

        if (preg_match('/cURL error 28|timed out/i', $message)) {
            return 'انتهت مهلة الاتصال ببوابة تكامل.';
        }

        if (preg_match('/SSL|certificate/i', $message)) {
            return 'فشل التحقق من شهادة TLS الخاصة ببوابة تكامل.';
        }

        return 'حدث خطأ أثناء الاتصال بخدمة OAuth في تكامل.';
    }
}
