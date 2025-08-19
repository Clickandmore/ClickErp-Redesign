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

class IstiqdasmController extends Controller
{
    protected $istiqdasmService;

    public function __construct(IstiqdasmService $istiqdasmService)
    {
        $this->istiqdasmService = $istiqdasmService;
    }

    /**
     * Display the main Istiqdam dashboard
     */
    public function index()
    {
        return view('admin.istiqdam.index');
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        $result = $this->istiqdasmService->testConnection();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'تم الاتصال بمنصة الاستقدام بنجاح' : $result['error'],
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * Show packages page
     */
    public function packages()
    {
        return view('admin.istiqdam.packages');
    }

    /**
     * Get packages via AJAX
     */
    public function getPackages(Request $request)
    {
        $filters = [
            'size' => $request->get('size', 10),
            'page' => $request->get('page', 0),
            'sort' => $request->get('sort', 'createdAt,desc'),
            'packageId' => $request->get('packageId', ''),
            'duration' => $request->get('duration', ''),
            'nationality' => $request->get('nationality', ''),
            'occupation' => $request->get('occupation', ''),
            'gender' => $request->get('gender', ''),
            'priceGrossMin' => $request->get('priceGrossMin', ''),
            'priceGrossMax' => $request->get('priceGrossMax', ''),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });

        $result = $this->istiqdasmService->getPackages($filters);

        return response()->json($result);
    }

    /**
     * Get specific package details
     */
    public function getPackage($packageId)
    {
        $result = $this->istiqdasmService->getPackage($packageId);

        return response()->json($result);
    }

    /**
     * Show contracts page
     */
    public function contracts()
    {
        $contracts = IstiqdasmContract::with(['assignedMaid', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.istiqdam.contracts', compact('contracts'));
    }

    /**
     * Show create contract form
     */
    public function createContract()
    {
        // Get clients for the form
        $clients = User::select('id', 'name', 'phone', 'national_num')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.istiqdam.create-contract', compact('clients'));
    }

    /**
     * Store new contract in Istiqdam platform
     */
    public function storeContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'beneficiary_id' => 'required|string',
            'beneficiary_birthdate' => 'required|date',
            'package_id' => 'required|integer',
            'start_date' => 'required|date',
            'special_specification' => 'nullable|string',

            // Beneficiary address
            'city' => 'required|string',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',

            // Contact person
            'contact_person_name' => 'required|string',
            'contact_person_phone' => 'required|string',

            // Phone numbers
            'mobile_phone' => 'required|string',
            'home_phone' => 'nullable|string',
            'second_phone' => 'nullable|string',
            'work_phone' => 'nullable|string',
            'redirect_phone' => 'nullable|string',
            'email' => 'required|email',

            // Payment details
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'required|string',
            'down_payment_amount' => 'required|numeric|min:0',
            'down_payment_due_date' => 'required|date',

            // Commitments
            'notice_period' => 'required|integer|min:1',
            'replacement_times' => 'required|integer|min:0',
            'travel_days' => 'required|integer|min:0',
            'travel_hours' => 'required|integer|min:0',
            'transportation_costs' => 'required|numeric|min:0',
            'admin_fee_percent' => 'required|numeric|min:0',
            'termination_fee' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Prepare contract data for Istiqdam API
            $contractData = [
                'beneficiary' => [
                    'id' => $request->beneficiary_id,
                    'birthdate' => $request->beneficiary_birthdate,
                ],
                'startAt' => $request->start_date . 'T00:00:00Z',
                'package' => [
                    'id' => (int) $request->package_id,
                ],
                'details' => [
                    'specialSpecification' => $request->special_specification ?? '',
                    'beneficiary' => [
                        'address' => [
                            'city' => $request->city,
                            'longitude' => (float) $request->longitude,
                            'latitude' => (float) $request->latitude,
                        ],
                        'contact' => [
                            'person' => [
                                'name' => $request->contact_person_name,
                                'phoneNumber' => $request->contact_person_phone,
                            ],
                            'phoneNumber' => [
                                'mobile' => $request->mobile_phone,
                                'home' => $request->home_phone ?? '',
                                'second' => $request->second_phone ?? '',
                                'work' => $request->work_phone ?? '',
                                'redirect' => $request->redirect_phone ?? '',
                            ],
                            'email' => $request->email,
                        ],
                    ],
                    'payment' => [
                        'discountPercentage' => (float) ($request->discount_percentage ?? 0),
                        'paymentMethod' => $request->payment_method,
                        'paymentDetails' => [
                            [
                                'type' => 'DOWN_PAYMENT',
                                'dueDate' => $request->down_payment_due_date,
                                'grossPrice' => (float) $request->down_payment_amount,
                            ]
                        ],
                    ],
                    'commitments' => [
                        'firstParty' => [
                            'noticePeriod' => (int) $request->notice_period,
                            'replacementTimes' => (int) $request->replacement_times,
                        ],
                        'secondParty' => [
                            'travelAccompaniment' => [
                                'days' => (int) $request->travel_days,
                                'hours' => (int) $request->travel_hours,
                            ],
                            'laborerTransportationCosts' => (float) $request->transportation_costs,
                            'administrativeFeePercent' => (float) $request->admin_fee_percent,
                            'terminationFee' => (float) $request->termination_fee,
                        ],
                    ],
                ],
            ];

            // Send to Istiqdam API
            $result = $this->istiqdasmService->createContract($contractData);

            if (!$result['success']) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

                        // Store contract reference in our database
            $localContract = IstiqdasmContract::createFromIstiqdam($result['data'], [
                'beneficiary_name' => $request->contact_person_name,
                'total_amount' => $request->down_payment_amount,
                'contract_number' => 'IST-' . date('Ymd') . '-' . str_pad($result['contract_id'], 6, '0', STR_PAD_LEFT),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد في منصة الاستقدام بنجاح',
                'data' => $result['data'],
                'contract_id' => $result['contract_id']
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating Istiqdam contract: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد'
            ], 500);
        }
    }

    /**
     * Get contract details
     */
    public function getContractDetails($contractId)
    {
        $result = $this->istiqdasmService->getContractDetails($contractId);

        return response()->json($result);
    }

    /**
     * Cancel contract
     */
    public function cancelContract($contractId)
    {
        $result = $this->istiqdasmService->cancelContract($contractId);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'تم إلغاء العقد بنجاح' : $result['error'],
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * Show work permit assignment form
     */
    public function showWorkPermitForm($contractId)
    {
        // Get available maids
        $maids = Maid::select('id', 'name', 'passport_num', 'nationality_id')
            ->with('nationality')
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        return view('admin.istiqdam.work-permit', compact('contractId', 'maids'));
    }

    /**
     * Assign work permit (maid) to contract
     */
    public function assignWorkPermit(Request $request, $contractId)
    {
        $validator = Validator::make($request->all(), [
            'work_permit_number' => 'required|string',
            'maid_id' => 'required|exists:maids,id',
            'maid_birthdate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $maid = Maid::find($request->maid_id);

        $workPermitData = [
            'number' => $request->work_permit_number,
            'laborer' => [
                'id' => $maid->passport_num, // or national ID
                'birthdate' => $request->maid_birthdate,
            ]
        ];

        $result = $this->istiqdasmService->assignWorkPermit($contractId, $workPermitData);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'تم إضافة العاملة للعقد بنجاح' : $result['error'],
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * Download contract document
     */
    public function downloadContract($contractId, $type = 'pdf')
    {
        $result = $this->istiqdasmService->downloadContract($contractId, $type);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 400);
        }

        $filename = "istiqdam_contract_{$contractId}.{$type}";

        return response($result['data'])
            ->header('Content-Type', $result['content_type'])
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
