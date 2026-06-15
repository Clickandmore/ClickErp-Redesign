<?php

namespace App\Http\Controllers\admin;

use App\Models\IstiqdasmContract;
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
        return view('admin.istiqdam.real-index');
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
                        'environment' => config('istiqdam.current_env'),
                        'base_url' => config('istiqdam.environments.' . config('istiqdam.current_env') . '.base_url'),
                        'timestamp' => now()->toISOString(),
                        'api_response' => $result['data'] ?? null
                    ]
                ]);
            } else {
                // إضافة معلومات تشخيص إضافية
                $diagnosis = [
                    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'غير متوفر',
                    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'غير متوفر',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'غير متوفر',
                    'timestamp' => now()->toISOString(),
                ];

                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال بمنصة الاستقدام: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'error_details' => $result,
                    'diagnosis' => $diagnosis,
                    'next_steps' => [
                        'تواصل مع الدعم الفني للمنصة الحكومية',
                        'قدم Support ID: ' . ($result['support_id'] ?? 'غير متوفر'),
                        'تحقق من إعدادات IP الخادم',
                        'تأكد من صحة بيانات الاعتماد'
                    ]
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاتصال: ' . $e->getMessage()
            ], 500);
        }
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
                $data = $result['data']['content'] ?? [];
                $totalRecords = $result['data']['totalElements'] ?? 0;

                return response()->json([
                    'draw' => $request->input('draw'),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $totalRecords,
                    'data' => $data,
                    'api_info' => [
                        'environment' => config('istiqdam.current_env'),
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
                        'environment' => config('istiqdam.current_env'),
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

            // API returns contract number in the "number" field (not "contractId")
            $contractNumber = $result['data']['number'] ?? null;

            // Store local reference
            $localContract = IstiqdasmContract::create([
                'istiqdam_contract_id' => $contractNumber,
                'contract_number'      => 'IST-' . date('Ymd') . '-' . str_pad($contractNumber, 6, '0', STR_PAD_LEFT),
                'beneficiary_id'       => $request->beneficiary_id,
                'beneficiary_name'     => $request->contact_person_name,
                'package_id'           => $request->package_id,
                'status'               => 'pending',
                'total_amount'         => $request->down_payment_amount,
                'created_by'           => auth('admin')->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد في منصة الاستقدام الحكومية بنجاح!',
                'data'    => $result['data'],
                'local_contract' => [
                    'id'              => $localContract->id,
                    'contract_number' => $localContract->contract_number,
                ],
                'api_info' => [
                    'environment' => config('istiqdam.current_env'),
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
                        'environment' => config('istiqdam.current_env'),
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
                        'environment' => config('istiqdam.current_env'),
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
                        'environment' => config('istiqdam.current_env'),
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
            $diagnosis = [
                'timestamp' => now()->toISOString(),
                'environment' => config('istiqdam.current_env'),
                'base_url' => config('istiqdam.environments.' . config('istiqdam.current_env') . '.base_url'),
                'client_id' => config('istiqdam.environments.' . config('istiqdam.current_env') . '.client_id'),
                'api_key_length' => strlen(config('istiqdam.environments.' . config('istiqdam.current_env') . '.api_key')),
                'server_info' => [
                    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'غير متوفر',
                    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'غير متوفر',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'غير متوفر',
                ]
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

}
