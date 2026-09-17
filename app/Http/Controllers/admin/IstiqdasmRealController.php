<?php

namespace App\Http\Controllers\admin;

use App\Models\IstiqdasmContract;
use App\Models\IstiqdamSetting;
use App\Services\IstiqdasmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IstiqdasmRealController extends Controller
{
    protected $istiqdasmService;

    public function __construct(IstiqdasmService $istiqdasmService)
    {
        $this->istiqdasmService = $istiqdasmService;
    }

    /**
     * Display the main Istiqdam dashboard with real connection
     */
    public function index()
    {
        $settings = IstiqdamSetting::query()->first();

        return view('admin.istiqdam.real-index', [
            'environment' => $this->istiqdasmService->environment(),
            'connectionTimeout' => $this->istiqdasmService->timeout(),
            'istiqdamSettings' => $settings,
            'isConfigured' => $this->isConfigured($settings),
            'contractSummary' => [
                'total' => IstiqdasmContract::count(),
                'pending' => IstiqdasmContract::where('status', 'pending_approval')->count(),
                'active' => IstiqdasmContract::where('status', 'active')->count(),
                'cancelled' => IstiqdasmContract::where('status', 'cancelled')->count(),
            ],
            'recentContracts' => IstiqdasmContract::latest()->take(5)->get(),
        ]);
    }

    /** Display the credentials and connection setup separately from operations. */
    public function settings()
    {
        $settings = IstiqdamSetting::query()->first();

        return view('admin.istiqdam.settings', [
            'environment' => $settings?->environment ?: 'staging',
            'istiqdamSettings' => $settings,
            'isConfigured' => $this->isConfigured($settings),
        ]);
    }

    /** Save the operational credentials received from Takamol. */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'establishment_name' => ['nullable', 'string', 'max:255'],
            'environment' => ['required', 'in:staging,uat,production'],
            'base_url' => ['required', 'url', 'starts_with:https://', 'max:1000'],
            'oauth_url' => ['required', 'url', 'starts_with:https://', 'max:1000'],
            'outbound_public_ip' => ['required', 'ip'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:2000'],
            'api_key' => ['nullable', 'string', 'max:2000'],
            'remove_api_key' => ['nullable', 'boolean'],
            'scope' => ['required', 'string', 'max:255'],
            'timeout' => ['required', 'integer', 'min:1', 'max:300'],
            'connect_timeout' => ['required', 'integer', 'min:1', 'max:120', 'lte:timeout'],
            'token_cache_ttl' => ['required', 'integer', 'min:60', 'max:86400'],
            'logging_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = IstiqdamSetting::query()->firstOrNew();
        $settings->establishment_name = $validated['establishment_name'] ?? null;
        $settings->environment = $validated['environment'];
        $settings->base_url = rtrim($validated['base_url'], '/');
        $settings->oauth_url = $validated['oauth_url'];
        $settings->outbound_public_ip = $validated['outbound_public_ip'];
        $settings->client_id = $validated['client_id'];
        $settings->scope = $validated['scope'];
        $settings->timeout = $validated['timeout'];
        $settings->connect_timeout = $validated['connect_timeout'];
        $settings->token_cache_ttl = $validated['token_cache_ttl'];
        $settings->logging_enabled = $request->boolean('logging_enabled');
        if ($request->filled('client_secret')) {
            $settings->client_secret = $validated['client_secret'];
        }
        // API KEY اختياري: تكامل تصدر Client-Id/Secret فقط في الغالب.
        if ($request->boolean('remove_api_key')) {
            $settings->api_key = null;
        } elseif ($request->filled('api_key')) {
            $settings->api_key = $validated['api_key'];
        }
        $settings->updated_by = auth('admin')->id();

        if (!$settings->exists && !$request->filled('client_secret')) {
            return back()->withErrors(['client_secret' => 'أدخل Client Secret عند حفظ الإعداد لأول مرة.'])->withInput();
        }

        $this->istiqdasmService->clearTokenCache();
        $settings->save();

        return redirect()->route('istiqdam.settings')->with('success', 'تم حفظ إعدادات الربط بأمان. يمكنك الآن اختبار الاتصال وتشغيل العمليات.');
    }

    /**
     * Test real connection to Istiqdam API
     */
    public function testConnection()
    {
        try {
            $result = $this->istiqdasmService->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم الاتصال بمنصة الاستقدام الحكومية بنجاح!',
                    'data' => [
                        'environment' => $this->istiqdasmService->environment(),
                        'base_url' => $this->istiqdasmService->baseUrl(),
                        'timestamp' => now()->toISOString(),
                        'api_response' => $result['data'] ?? null
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال بمنصة الاستقدام: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'provider_error' => $this->providerErrorReport($result),
                    'error_details' => $result,
                    'next_steps' => $this->nextSteps($result),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاتصال: ' . $e->getMessage(),
                'provider_error' => $this->providerErrorReport([
                    'stage' => 'exception',
                    'provider' => ['raw_error' => $e->getMessage()],
                ]),
            ], 500);
        }
    }

    /**
     * تقرير جاهز للنسخ وإرساله لدعم تكامل: ما الذي فشل، وماذا ردّت البوابة حرفياً.
     * لا يتضمّن Client Secret ولا API KEY.
     */
    private function providerErrorReport(array $result): array
    {
        $provider = $result['provider'] ?? [];
        $settings = IstiqdamSetting::query()->first();
        $env = $this->istiqdasmService->environment();

        $stageLabels = [
            'oauth' => 'مصادقة OAuth (طلب التوكن)',
            'api' => 'استدعاء واجهة الباقات بعد نجاح المصادقة',
            'network' => 'فتح اتصال شبكي مع البوابة',
            'exception' => 'خطأ داخلي أثناء الطلب',
        ];
        $stage = $provider['stage'] ?? ($result['stage'] ?? 'unknown');

        $report = [
            'stage' => $stage,
            'stage_label' => $stageLabels[$stage] ?? 'غير محدد',
            'environment' => $env,
            'endpoint' => $provider['endpoint'] ?? $this->istiqdasmService->oauthUrl(),
            'client_id' => $settings?->client_id,
            'api_key_sent' => (bool) $settings?->api_key,
            'http_status' => $provider['http_status'] ?? ($result['status_code'] ?? null),
            'provider_message' => $provider['provider_message'] ?? null,
            'provider_body' => $provider['provider_body'] ?? null,
            'raw_error' => $provider['raw_error'] ?? null,
            'gateway_trace' => $provider['gateway_trace'] ?? [],
            'outbound_public_ip' => $settings?->outbound_public_ip,
            'internal_server_ip' => request()->server('SERVER_ADDR'),
            'captured_at' => $provider['captured_at'] ?? now()->toDateTimeString(),
        ];

        return array_filter($report, static fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    /**
     * خطوات المعالجة المقترحة حسب المرحلة التي فشلت فعلياً.
     */
    private function nextSteps(array $result): array
    {
        $stage = $result['provider']['stage'] ?? ($result['stage'] ?? null);

        if ($stage === 'network') {
            return [
                'الطلب لم يصل إلى تكامل أصلاً؛ المشكلة شبكية وليست في بيانات الاعتماد.',
                'أرسل عنوان IP الخاص بالخادم لدعم تكامل واطلب إضافته إلى قائمة السماح (Whitelist).',
                'تأكد من أن جدار الحماية يسمح بالمنفذ 443 نحو بوابة تكامل.',
            ];
        }

        if ($stage === 'oauth') {
            return [
                'رفضت بوابة تكامل بيانات الاعتماد؛ أعد التحقق من Client ID وClient Secret في صفحة الإعدادات.',
                'أرسل نص رد البوابة أدناه مع معرّف التتبّع لدعم تكامل.',
            ];
        }

        return [
            'نجحت المصادقة وفشل الاستدعاء بعدها؛ المشكلة في الصلاحيات أو الباقة وليست في المفاتيح.',
            'أرسل نص رد البوابة أدناه مع معرّف التتبّع لدعم تكامل.',
        ];
    }

    /**
     * Get real packages from Istiqdam API
     */
    public function getPackages(Request $request)
    {
        try {
            $page = intval($request->input('start', 0) / $request->input('length', 10));
            $size = $request->input('length', 10);

            // Get filters from request
            $filters = $request->only([
                'packageId', 'duration', 'nationality',
                'occupation', 'gender', 'priceGrossMin', 'priceGrossMax'
            ]);

            $result = $this->istiqdasmService->getPackages($page, $size, $filters);

            if ($result['success']) {
                $payload = $this->apiPayload($result['data'] ?? []);
                $data = $payload['content'] ?? [];
                $totalRecords = $payload['total'] ?? ($payload['totalElements'] ?? count($data));

                return response()->json([
                    'draw' => $request->input('draw'),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $totalRecords,
                    'data' => $data,
                    'api_info' => [
                        'environment' => $this->istiqdasmService->environment(),
                        'real_api' => true
                    ]
                ]);
            } else {
                return response()->json([
                    'error' => 'فشل في جلب الباقات من منصة الاستقدام: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'details' => $result
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the real contract form and load its packages directly from Takamol.
     */
    public function createContract()
    {
        $result = $this->istiqdasmService->getPackages(0, 1000);

        if (!$result['success']) {
            return redirect()
                ->route('istiqdam.index')
                ->with('error', 'تعذر جلب الباقات من منصة تكامل: ' . ($result['error'] ?? 'خطأ غير معروف'));
        }

        $payload = $this->apiPayload($result['data'] ?? []);

        return view('admin.istiqdam.real-create-contract', [
            'packages' => $payload['content'] ?? [],
            'totalRecords' => $payload['total'] ?? ($payload['totalElements'] ?? 0),
            'apiEnvironment' => $this->istiqdasmService->environment(),
        ]);
    }

    /**
     * Get real package details from Istiqdam API
     */
    public function getPackage($packageId)
    {
        try {
            $result = $this->istiqdasmService->getPackage($packageId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'api_info' => [
                        'environment' => $this->istiqdasmService->environment(),
                        'real_api' => true,
                        'package_id' => $packageId
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في جلب تفاصيل الباقة: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'error_details' => $result
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a real contract in Istiqdam platform
     */
    public function storeContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'beneficiary_id'                  => 'required|string',
            'beneficiary_birthdate'           => 'required|date',
            'start_at'                        => 'required|date',
            'package_id'                      => 'required|integer',
            'contact_person_name'             => 'required|string',
            'contact_person_phone_number'     => 'required|string',
            'contact_mobile_number'           => 'required|string',
            'contact_second_number'           => 'nullable|string',
            'contact_home_number'             => 'nullable|string',
            'contact_work_number'             => 'nullable|string',
            'contact_redirect'                => 'nullable|string',
            'contact_email'                   => 'nullable|email',
            'beneficiary_city'               => 'nullable|string',
            'beneficiary_district'           => 'nullable|string',
            'beneficiary_postal_code'        => 'nullable|string',
            'beneficiary_short_address'      => 'nullable|string',
            'special_specification'           => 'nullable|string',
            'down_payment_amount'             => 'required|numeric|min:0',
            'down_payment_due_date'           => 'required|date',
            'payment_method'                  => 'required|string',
            'discount_percentage'             => 'nullable|numeric|min:0|max:100',
            'notice_period'                   => 'nullable|integer',
            'replacement_times'               => 'nullable|integer',
            'travel_accompaniment_days'       => 'nullable|integer',
            'travel_accompaniment_hours'      => 'nullable|integer',
            'laborer_transportation_costs'    => 'nullable|numeric',
            'administrative_fee_percent'      => 'nullable|numeric',
            'termination_fee'                 => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Build the full payload per Istiqdam API v1.2 documentation
            $contractData = [
                'beneficiary' => [
                    'id'        => $request->beneficiary_id,
                    'birthdate' => $request->beneficiary_birthdate,
                ],
                'startAt' => date('Y-m-d', strtotime($request->start_at)) . 'T00:00:00Z',
                'package' => [
                    'id' => (int) $request->package_id,
                ],
                'details' => [
                    'specialSpecification' => $request->special_specification ?? '',
                    'beneficiary' => [
                        'address' => [
                            'city' => $request->beneficiary_city ?? '',
                            'district' => $request->beneficiary_district,
                            'postalCode' => $request->beneficiary_postal_code,
                            'shortAddress' => $request->beneficiary_short_address,
                        ],
                        'contact' => [
                            'person' => [
                                'name'        => $request->contact_person_name,
                                'phoneNumber' => $request->contact_person_phone_number,
                            ],
                            'phoneNumber' => [
                                'mobile'   => $request->contact_mobile_number,
                                'second'   => $request->contact_second_number ?? '',
                                'home'     => $request->contact_home_number ?? '',
                                'work'     => $request->contact_work_number ?? '',
                                'redirect' => $request->contact_redirect ?? '',
                            ],
                            'email' => $request->contact_email ?? '',
                        ],
                    ],
                    'payment' => [
                        'discountPercentage' => (float) ($request->discount_percentage ?? 0),
                        'paymentMethod'      => $request->payment_method,
                        'paymentDetails'     => [
                            [
                                'type'       => 'DOWN_PAYMENT',
                                'dueDate'    => $request->down_payment_due_date,
                                'grossPrice' => (float) $request->down_payment_amount,
                            ],
                        ],
                    ],
                    'commitments' => [
                        'firstParty' => [
                            'noticePeriod'     => (int) ($request->notice_period ?? 24),
                            'replacementTimes' => (int) ($request->replacement_times ?? 1),
                        ],
                        'secondParty' => [
                            'travelAccompaniment' => [
                                'days'  => (int) ($request->travel_accompaniment_days ?? 1),
                                'hours' => (int) ($request->travel_accompaniment_hours ?? 1),
                            ],
                            'laborerTransportationCosts' => (float) ($request->laborer_transportation_costs ?? 1),
                            'administrativeFeePercent'   => (float) ($request->administrative_fee_percent ?? 2.5),
                            'terminationFee'             => (float) ($request->termination_fee ?? 2.5),
                        ],
                    ],
                ],
            ];

            // Call Istiqdam API
            $result = $this->istiqdasmService->createContract($contractData);

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success'      => false,
                    'message'      => 'فشل في إنشاء العقد في منصة الاستقدام: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'error_details' => $result,
                ], 400);
            }

            $apiPayload = $this->apiPayload($result['data'] ?? []);
            $contractNumber = $apiPayload['number']
                ?? $apiPayload['contractId']
                ?? $apiPayload['id']
                ?? null;

            if (!$contractNumber) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'أنشأت المنصة الاستجابة دون إرجاع رقم العقد؛ لم يتم حفظ سجل محلي.',
                    'error_details' => $result['data'] ?? null,
                ], 502);
            }

            // Store local reference
            $localContract = IstiqdasmContract::create([
                'istiqdam_contract_id' => $contractNumber,
                'contract_number'      => 'IST-' . date('Ymd') . '-' . str_pad($contractNumber, 6, '0', STR_PAD_LEFT),
                'beneficiary_id'       => $request->beneficiary_id,
                'beneficiary_birthdate' => $request->beneficiary_birthdate,
                'beneficiary_name'     => $request->contact_person_name,
                'package_id'           => $request->package_id,
                'package_details'      => $apiPayload['package'] ?? ['id' => (int) $request->package_id],
                'start_date'           => $request->start_at,
                'status'               => 'pending_approval',
                'contact_details'      => $contractData['details']['beneficiary']['contact'],
                'address_details'      => $contractData['details']['beneficiary']['address'],
                'payment_details'      => $contractData['details']['payment'],
                'total_amount'         => $request->down_payment_amount,
                'remaining_amount'     => $request->down_payment_amount,
                'commitments'          => $contractData['details']['commitments'],
                'special_specification' => $request->special_specification,
                'last_api_response'    => $result['data'],
                'last_synced_at'       => now(),
                'created_by'           => auth('admin')->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد في منصة الاستقدام الحكومية بنجاح!',
                'data'    => $result['data'],
                'platform_contract_number' => (string) $contractNumber,
                'local_contract' => [
                    'id'              => $localContract->id,
                    'contract_number' => $localContract->contract_number,
                ],
                'api_info' => [
                    'environment' => $this->istiqdasmService->environment(),
                    'real_api'    => true,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating real Istiqdam contract: ' . $e->getMessage(), [
                'exception'    => $e,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign work permit to a contract
     * POST /takamol/staging/api/v1/external/workPermitAssignment/{contractId}
     */
    public function assignWorkPermit(Request $request, $contractId)
    {
        $validator = Validator::make($request->all(), [
            'work_permit_number' => 'required|string',
            'laborer_id'         => 'required|string',
            'laborer_birthdate'  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $workPermitData = [
                'number'  => $request->work_permit_number,
                'laborer' => [
                    'id'        => $request->laborer_id,
                    'birthdate' => $request->laborer_birthdate,
                ],
            ];

            $result = $this->istiqdasmService->assignWorkPermit($contractId, $workPermitData);

            if ($result['success']) {
                // Persist work permit data locally
                $localContract = IstiqdasmContract::where('istiqdam_contract_id', $contractId)->first();
                if ($localContract) {
                    $localContract->update([
                        'work_permits' => $result['data'],
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إضافة العاملة للعقد بنجاح',
                    'data'    => $result['data'],
                    'api_info' => [
                        'environment' => $this->istiqdasmService->environment(),
                        'real_api'    => true,
                    ],
                ]);
            }

            return response()->json([
                'success'      => false,
                'message'      => 'فشل في إضافة العاملة للعقد: ' . ($result['error'] ?? 'خطأ غير معروف'),
                'error_details' => $result,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real contract details from Istiqdam
     */
    public function getContractDetails($contractId)
    {
        try {
            $result = $this->istiqdasmService->getContractDetails($contractId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'api_info' => [
                        'environment' => $this->istiqdasmService->environment(),
                        'real_api' => true,
                        'contract_id' => $contractId
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في جلب تفاصيل العقد: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'error_details' => $result
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel real contract in Istiqdam
     */
    public function cancelContract($contractId)
    {
        try {
            $result = $this->istiqdasmService->cancelContract($contractId);

            if ($result['success']) {
                // Update local contract status
                $localContract = IstiqdasmContract::where('istiqdam_contract_id', $contractId)->first();
                if ($localContract) {
                    $localContract->update(['status' => 'cancelled']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء العقد في منصة الاستقدام بنجاح',
                    'data' => $result['data'],
                    'api_info' => [
                        'environment' => $this->istiqdasmService->environment(),
                        'real_api' => true
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في إلغاء العقد: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'error_details' => $result
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download real contract from Istiqdam
     */
    public function downloadContract($contractId, $type = 'pdf')
    {
        try {
            $result = $this->istiqdasmService->downloadContract($contractId, $type);

            if ($result['success']) {
                return response($result['data'])
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "attachment; filename=\"istiqdam_contract_{$contractId}.{$type}\"");
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تحميل العقد: ' . ($result['error'] ?? 'خطأ غير معروف')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تشخيص مشاكل الاتصال
     */
    public function diagnoseConnection()
    {
        try {
            $storedSettings = IstiqdamSetting::query()->first();
            $credentialsConfigured = (bool) ($storedSettings?->client_id && $storedSettings?->client_secret);

            $diagnosis = [
                'timestamp' => now()->toISOString(),
                'environment' => $this->istiqdasmService->environment(),
                'base_url' => $this->istiqdasmService->baseUrl(),
                'api_key_length' => strlen((string) $storedSettings?->api_key),
                'credentials_configured' => $credentialsConfigured,
            ];

            // اختبار الاتصال
            $connectionTest = $this->istiqdasmService->testConnection();
            $diagnosis['connection_test'] = $connectionTest;

            return response()->json([
                'success' => true,
                'message' => 'تم تشخيص الاتصال',
                'diagnosis' => $diagnosis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في التشخيص: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Takamol staging wraps business data in body.response, while other
     * environments may return the business payload directly.
     */
    private function apiPayload(array $data): array
    {
        $payload = $data['body']['response']
            ?? $data['response']
            ?? $data['body']
            ?? $data;

        return is_array($payload) ? $payload : [];
    }

    private function isConfigured(?IstiqdamSetting $settings): bool
    {
        // API KEY ليس شرطاً؛ المصادقة تتم بـ Client-Id وClient-Secret.
        return (bool) ($settings?->client_id && $settings?->client_secret);
    }

}
