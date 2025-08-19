<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\IstiqdasmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Models\ingaz\Maid;
use App\Models\IstiqdasmContract;

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
                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال بمنصة الاستقدام: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'error_details' => $result
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
            'beneficiary_id' => 'required|string',
            'beneficiary_birthdate' => 'required|date',
            'start_at' => 'required|date',
            'package_id' => 'required|integer',
            'contact_person_name' => 'required|string',
            'contact_person_phone_number' => 'required|string',
            'contact_mobile_number' => 'required|string',
            'contact_email' => 'nullable|email',
            'down_payment_amount' => 'required|numeric|min:0',
            'down_payment_due_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Prepare contract data for Istiqdam API
            $contractData = [
                'beneficiary' => [
                    'id' => $request->beneficiary_id,
                    'birthdate' => $request->beneficiary_birthdate,
                ],
                'startAt' => $request->start_at . 'T00:00:00Z',
                'package' => [
                    'id' => (int)$request->package_id,
                ],
                'details' => [
                    'beneficiary' => [
                        'contact' => [
                            'person' => [
                                'name' => $request->contact_person_name,
                                'phoneNumber' => $request->contact_person_phone_number,
                            ],
                            'phoneNumber' => [
                                'mobile' => $request->contact_mobile_number,
                            ],
                            'email' => $request->contact_email,
                        ],
                    ],
                    'payment' => [
                        'paymentMethod' => $request->payment_method,
                        'paymentDetails' => [
                            [
                                'type' => 'DOWN_PAYMENT',
                                'dueDate' => $request->down_payment_due_date,
                                'grossPrice' => (float)$request->down_payment_amount,
                            ],
                        ],
                    ],
                ],
            ];

            // Send to real Istiqdam API
            $result = $this->istiqdasmService->createContract($contractData);

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في إنشاء العقد في منصة الاستقدام: ' . ($result['error'] ?? 'خطأ غير معروف'),
                    'error_details' => $result
                ], 400);
            }

            // Store contract reference in our database
            $localContract = IstiqdasmContract::create([
                'istiqdam_contract_id' => $result['data']['contractId'],
                'contract_number' => 'IST-' . date('Ymd') . '-' . str_pad($result['data']['contractId'], 6, '0', STR_PAD_LEFT),
                'beneficiary_id' => $request->beneficiary_id,
                'beneficiary_name' => $request->contact_person_name,
                'package_id' => $request->package_id,
                'status' => 'pending',
                'total_amount' => $request->down_payment_amount,
                'created_by' => auth('admin')->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد في منصة الاستقدام الحكومية بنجاح!',
                'data' => $result['data'],
                'local_contract' => [
                    'id' => $localContract->id,
                    'contract_number' => $localContract->contract_number,
                ],
                'api_info' => [
                    'environment' => config('istiqdam.current_env'),
                    'real_api' => true
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating real Istiqdam contract: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage()
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
}
