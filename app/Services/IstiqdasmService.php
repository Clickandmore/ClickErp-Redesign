<?php

namespace App\Services;

use App\Models\IstiqdamSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
    protected bool $loggingEnabled;

    /** Raw failure details from the gateway, to be shown to the admin and forwarded to Takamol support. */
    protected array $lastAuthenticationDetails = [];

    public function __construct()
    {
        try {
            $storedSettings = IstiqdamSetting::query()->first();
        } catch (\Throwable $exception) {
            $storedSettings = null;
        }

        $this->environment = (string) ($storedSettings?->environment ?: 'staging');
        $this->baseUrl = rtrim((string) ($storedSettings?->base_url ?: 'https://gw-apic.qiwa.info'), '/');
        $this->oauthUrl = (string) ($storedSettings?->oauth_url ?: 'https://gw-apic.qiwa.info/takamol/staging/takamol-oauth/oauth2/token');
        $this->clientId = (string) ($storedSettings?->client_id ?: '');
        $this->clientSecret = (string) ($storedSettings?->client_secret ?: '');
        $this->apiKey = (string) ($storedSettings?->api_key ?: '');
        $this->scope = (string) ($storedSettings?->scope ?: 'IntegrationScope');
        $this->timeout = (int) ($storedSettings?->timeout ?: 30);
        $this->connectTimeout = (int) ($storedSettings?->connect_timeout ?: 10);
        $this->tokenCacheTtl = (int) ($storedSettings?->token_cache_ttl ?: 3500);
        $this->loggingEnabled = (bool) ($storedSettings?->logging_enabled ?? true);
        $this->tokenCacheKey = 'istiqdam_oauth_token_' . sha1($this->environment . '|' . $this->baseUrl . '|' . $this->clientId);
    }

    public function environment(): string { return $this->environment; }
    public function baseUrl(): string { return $this->baseUrl; }
    public function oauthUrl(): string { return $this->oauthUrl; }
    public function timeout(): int { return $this->timeout; }
    public function clearTokenCache(): void { Cache::forget($this->tokenCacheKey); }

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
                ->withHeaders($this->gatewayHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]))
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
            if ($this->loggingEnabled) {
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
                $this->lastAuthenticationDetails = $token ? [] : [
                    'stage'          => 'oauth',
                    'http_status'    => $response->status(),
                    'endpoint'       => $this->oauthUrl,
                    'provider_body'  => 'استجابة ناجحة بلا access_token (الجسم غير معروض لأنه قد يحتوي على بيانات اعتماد).',
                    'gateway_trace'  => $this->gatewayTrace($response),
                    'captured_at'    => now()->toDateTimeString(),
                ];

                return $token;
            }

            $responseBody = $response->json();
            $providerMessage = $this->providerMessage($responseBody);
            $this->lastAuthenticationError = 'رفضت بوابة OAuth الطلب (HTTP ' . $response->status() . ')' . ($providerMessage ? ': ' . $providerMessage : '.');
            $this->lastAuthenticationDetails = [
                'stage'            => 'oauth',
                'http_status'      => $response->status(),
                'endpoint'         => $this->oauthUrl,
                'provider_message' => $providerMessage,
                'provider_body'    => $this->readableBody($responseBody ?: $response->body()),
                'gateway_trace'    => $this->gatewayTrace($response),
                'captured_at'      => now()->toDateTimeString(),
            ];

            Log::channel('istiqdam')->error('Istiqdam OAuth2 token fetch failed', [
                'status'   => $response->status(),
                'response' => $responseBody,
                'trace'    => $this->gatewayTrace($response),
            ]);

            return null;

        } catch (\Exception $e) {
            $this->lastAuthenticationError = $this->connectionErrorMessage($e);
            $this->lastAuthenticationDetails = [
                'stage'         => 'network',
                'endpoint'      => $this->oauthUrl,
                'raw_error'     => $e->getMessage(),
                'captured_at'   => now()->toDateTimeString(),
            ];
            Log::channel('istiqdam')->error('Istiqdam OAuth2 exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * تفاصيل آخر فشل في المصادقة، كما جاءت من بوابة تكامل، لعرضها للمسؤول وإرسالها للدعم.
     */
    public function lastAuthenticationDetails(): array
    {
        return $this->lastAuthenticationDetails;
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
            ->withHeaders($this->gatewayHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ]))
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
        if (!$this->loggingEnabled) {
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
                'success'  => false,
                'error'    => $this->lastAuthenticationError
                    ?: 'فشل الحصول على OAuth2 Token؛ تحقق من Client ID وClient Secret.',
                'stage'    => 'oauth',
                'provider' => $this->lastAuthenticationDetails,
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
            'success'  => false,
            'error'    => $result['error'] ?? 'خطأ غير معروف',
            'stage'    => 'api',
            'provider' => [
                'stage'            => 'api',
                'http_status'      => $result['status_code'] ?? null,
                'endpoint'         => $this->baseUrl . $this->endpoint(
                    '/takamol/staging/api/v1/external/packages',
                    '/api/protected/istiqdam/packages'
                ),
                'provider_message' => $result['provider_message'] ?? null,
                'provider_body'    => $result['provider_body'] ?? null,
                'gateway_trace'    => $result['gateway_trace'] ?? [],
                'captured_at'      => now()->toDateTimeString(),
            ],
            'details'  => $result,
        ];
    }

    /**
     * Full connection diagnosis.
     */
    public function diagnoseConnection(): array
    {
        $diagnosis = [
            'timestamp'         => now()->toISOString(),
            'environment'       => $this->environment,
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
            'success'          => false,
            'error'            => $body['description'] ?? ($body['message'] ?? $defaultMsg),
            'status_code'      => $response->status(),
            'body'             => $body,
            'provider_message' => $this->providerMessage($body),
            'provider_body'    => $this->readableBody($body ?: $response->body()),
            'gateway_trace'    => $this->gatewayTrace($response),
        ];
    }

    /**
     * النص الذي ترسله بوابة تكامل نفسها لشرح سبب الرفض.
     */
    private function providerMessage($body): ?string
    {
        if (!is_array($body)) {
            return null;
        }

        $message = $body['error_description']
            ?? $body['description']
            ?? $body['moreInformation']
            ?? $body['message']
            ?? $body['httpMessage']
            ?? $body['error']
            ?? null;

        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        return $message !== null ? (string) $message : null;
    }

    /**
     * معرّفات التتبّع التي يطلبها دعم تكامل لمطابقة الطلب في سجلاتهم.
     */
    private function gatewayTrace($response): array
    {
        $wanted = ['X-Global-Transaction-ID', 'X-Global-Transaction-Id', 'X-Request-Id', 'X-Correlation-Id', 'Transaction-Id', 'Date'];
        $trace  = [];

        foreach ($wanted as $header) {
            $value = $response->header($header);
            if ($value !== null && $value !== '') {
                $trace[$header] = $value;
            }
        }

        return $trace;
    }

    /**
     * جسم الاستجابة بصيغة قابلة للنسخ في رسالة الدعم، مع حدّ أقصى للطول.
     */
    private function readableBody($body): ?string
    {
        if ($body === null || $body === '' || $body === []) {
            return null;
        }

        $text = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return mb_strlen((string) $text) > 4000
            ? mb_substr((string) $text, 0, 4000) . '…'
            : (string) $text;
    }

    /**
     * Headers required by the Takamol gateway.
     *
     * تكامل تصدر Client-Id وClient-Secret فقط، وهيدر API-KEY اختياري؛
     * إرساله فارغاً يجعل بوابة IBM ترفض الطلب، لذلك لا نرسله إلا إذا كان مضبوطاً.
     */
    private function gatewayHeaders(array $extra = []): array
    {
        $headers = [
            'X-IBM-Client-Id'     => $this->clientId,
            'X-IBM-Client-Secret' => $this->clientSecret,
        ];

        if ($this->apiKey !== '') {
            $headers['API-KEY'] = $this->apiKey;
        }

        return array_merge($headers, $extra);
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
