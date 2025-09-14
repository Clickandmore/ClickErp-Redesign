<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class IstiqdasmService
{
    protected $baseUrl;
    protected $apiKey;
    protected $clientId;
    protected $timeout;

    public function __construct()
    {
        $env = Config::get('istiqdam.current_env');
        $config = Config::get("istiqdam.environments.{$env}");

        $this->baseUrl = $config['base_url'];
        $this->apiKey = $config['api_key'];
        $this->clientId = $config['client_id'];
        $this->timeout = Config::get('istiqdam.timeout');
    }

    /**
     * Get HTTP client with default headers
     */
    private function getHttpClient()
    {
        return Http::timeout($this->timeout)
            ->withHeaders([
                'API-KEY' => $this->apiKey,
                'client-id' => $this->clientId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Asas-AlInjaz/1.0 (Laravel)',
            ])
            ->withOptions([
                'verify' => true,
                'http_errors' => false,
            ]);
    }

    /**
     * Log API request and response
     */
    private function logApiCall($method, $endpoint, $requestData = null, $response = null, $error = null)
    {
        if (!Config::get('istiqdam.logging.enabled')) {
            return;
        }

        $logData = [
            'method' => $method,
            'endpoint' => $endpoint,
            'request_data' => $requestData,
            'response_status' => $response ? $response->status() : null,
            'response_data' => $response ? $response->json() : null,
            'error' => $error,
            'timestamp' => now(),
        ];

        if ($error) {
            Log::channel('istiqdam')->error('Istiqdam API Error', $logData);
        } else {
            Log::channel('istiqdam')->info('Istiqdam API Call', $logData);
        }
    }

    /**
     * Create a new contract in Istiqdam platform
     */
    public function createContract($contractData)
    {
        try {
            $endpoint = '/api/protected/istiqdam/contract';
            $url = $this->baseUrl . $endpoint;

            $response = $this->getHttpClient()->post($url, $contractData);

            $this->logApiCall('POST', $endpoint, $contractData, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'contract_id' => $response->json()['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'فشل في إنشاء العقد',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            $this->logApiCall('POST', $endpoint ?? '', $contractData, null, $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطأ في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get contract details by ID
     */
    public function getContractDetails($contractId)
    {
        try {
            $endpoint = "/api/protected/istiqdam/contractDetails/{$contractId}";
            $url = $this->baseUrl . $endpoint;

            $response = $this->getHttpClient()->get($url);

            $this->logApiCall('GET', $endpoint, null, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'فشل في جلب تفاصيل العقد',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint ?? '', null, null, $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطأ في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a contract
     */
    public function cancelContract($contractId)
    {
        try {
            $endpoint = "/api/protected/istiqdam/cancelContract/{$contractId}";
            $url = $this->baseUrl . $endpoint;

            $response = $this->getHttpClient()->get($url);

            $this->logApiCall('GET', $endpoint, null, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'فشل في إلغاء العقد',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint ?? '', null, null, $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطأ في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available packages with filters
     */
    public function getPackages($filters = [])
    {
        try {
            $endpoint = '/api/protected/istiqdam/packages';

            $defaultFilters = [
                'size' => 10,
                'page' => 0,
                'sort' => 'createdAt,desc',
            ];

            $queryParams = array_merge($defaultFilters, $filters);
            $url = $this->baseUrl . $endpoint . '?' . http_build_query($queryParams);

            $response = $this->getHttpClient()->get($url);

            $this->logApiCall('GET', $endpoint, $queryParams, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            // معالجة خاصة لخطأ 529
            if ($response->status() === 529) {
                $errorData = $response->json();
                $supportId = $errorData['support-id'] ?? 'غير متوفر';

                return [
                    'success' => false,
                    'error' => 'خطأ في الخادم (529) - يرجى التواصل مع الدعم الفني. Support ID: ' . $supportId,
                    'status_code' => $response->status(),
                    'support_id' => $supportId,
                    'details' => $errorData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'فشل في جلب الباقات',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint ?? '', $filters, null, $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطأ في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get specific package details
     */
    public function getPackage($packageId)
    {
        try {
            $endpoint = "/api/protected/istiqdam/package/{$packageId}";
            $url = $this->baseUrl . $endpoint;

            $response = $this->getHttpClient()->get($url);

            $this->logApiCall('GET', $endpoint, null, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'فشل في جلب تفاصيل الباقة',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint ?? '', null, null, $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطأ في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Assign work permit (maid) to contract
     */
    public function assignWorkPermit($contractId, $workPermitData)
    {
        try {
            $endpoint = "/api/protected/istiqdam/workPermitAssignment/{$contractId}";
            $url = $this->baseUrl . $endpoint;

            $response = $this->getHttpClient()->post($url, $workPermitData);

            $this->logApiCall('POST', $endpoint, $workPermitData, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'فشل في إضافة العاملة للعقد',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            $this->logApiCall('POST', $endpoint ?? '', $workPermitData, null, $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطأ في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download contract document
     */
    public function downloadContract($contractId, $type = 'pdf')
    {
        try {
            $endpoint = "/api/protected/istiqdam/downloadContract/{$contractId}";
            $url = $this->baseUrl . $endpoint . "?type={$type}";

            $response = $this->getHttpClient()->get($url);

            $this->logApiCall('GET', $endpoint, ['type' => $type], $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->body(),
                    'content_type' => $response->header('Content-Type'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'فشل في تحميل العقد',
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            $this->logApiCall('GET', $endpoint ?? '', ['type' => $type], null, $e->getMessage());

            return [
                'success' => false,
                'error' => 'خطأ في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            // اختبار اتصال مبسط أولاً
            $endpoint = '/api/protected/istiqdam/packages';
            $url = $this->baseUrl . $endpoint . '?size=1&page=0';

            $response = $this->getHttpClient()->get($url);

            $this->logApiCall('GET', $endpoint, ['size' => 1, 'page' => 0], $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'تم الاتصال بمنصة الاستقدام بنجاح',
                    'data' => $response->json(),
                ];
            }

            // معالجة خاصة لخطأ 529
            if ($response->status() === 529) {
                $errorData = $response->json();
                $supportId = $errorData['support-id'] ?? 'غير متوفر';

                return [
                    'success' => false,
                    'error' => 'خطأ في الخادم (529) - يرجى التواصل مع الدعم الفني. Support ID: ' . $supportId,
                    'status_code' => $response->status(),
                    'support_id' => $supportId,
                    'details' => $errorData,
                    'suggestions' => [
                        'تأكد من أن IP الخادم مسموح له بالوصول',
                        'تحقق من صحة API Key و Client ID',
                        'تواصل مع الدعم الفني للمنصة الحكومية'
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'فشل في الاتصال بمنصة الاستقدام: ' . ($response->json()['message'] ?? 'خطأ غير معروف'),
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'فشل في الاتصال بمنصة الاستقدام: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * تشخيص مشاكل الاتصال
     */
    public function diagnoseConnection()
    {
        $diagnosis = [
            'timestamp' => now()->toISOString(),
            'environment' => config('istiqdam.current_env'),
            'base_url' => $this->baseUrl,
            'client_id' => $this->clientId,
            'api_key_length' => strlen($this->apiKey),
            'timeout' => $this->timeout,
        ];

        try {
            // اختبار الاتصال الأساسي
            $response = $this->getHttpClient()->get($this->baseUrl . '/health');
            $diagnosis['health_check'] = [
                'status' => $response->status(),
                'success' => $response->successful(),
            ];
        } catch (\Exception $e) {
            $diagnosis['health_check'] = [
                'error' => $e->getMessage(),
            ];
        }

        try {
            // اختبار نقطة النهاية الرئيسية
            $response = $this->getHttpClient()->get($this->baseUrl . '/api/protected/istiqdam/packages?size=1');
            $diagnosis['api_test'] = [
                'status' => $response->status(),
                'success' => $response->successful(),
                'response' => $response->json(),
            ];
        } catch (\Exception $e) {
            $diagnosis['api_test'] = [
                'error' => $e->getMessage(),
            ];
        }

        return $diagnosis;
    }
}
