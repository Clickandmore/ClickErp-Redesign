<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class IstiqdasmTestController extends Controller
{
    /**
     * Display the main Istiqdam dashboard
     */
    public function index()
    {
        return view('admin.istiqdam.test-simple');
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            // Simple test without external service for now
            return response()->json([
                'success' => true,
                'message' => 'النظام يعمل بشكل صحيح - جاري تطوير التكامل مع منصة الاستقدام',
                'data' => [
                    'status' => 'development',
                    'environment' => config('app.env'),
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show packages page
     */
    public function packages()
    {
        // Mock packages data for testing
        $mockPackages = [
            [
                'id' => 1001,
                'nationality' => 'الفلبين',
                'occupation' => 'خادمة منزلية',
                'duration' => 24,
                'gender' => 'female',
                'priceGross' => 15000,
                'status' => 'available'
            ],
            [
                'id' => 1002,
                'nationality' => 'إندونيسيا',
                'occupation' => 'طباخة',
                'duration' => 24,
                'gender' => 'female',
                'priceGross' => 18000,
                'status' => 'available'
            ],
            [
                'id' => 1003,
                'nationality' => 'بنجلاديش',
                'occupation' => 'عاملة نظافة',
                'duration' => 12,
                'gender' => 'female',
                'priceGross' => 12000,
                'status' => 'available'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'content' => $mockPackages,
                'totalElements' => count($mockPackages),
                'totalPages' => 1,
                'number' => 0,
                'size' => 10,
                'numberOfElements' => count($mockPackages)
            ]
        ]);
    }

    /**
     * Get specific package details
     */
    public function getPackage($packageId)
    {
        // Mock package details
        $mockPackage = [
            'id' => $packageId,
            'nationality' => 'الفلبين',
            'occupation' => 'خادمة منزلية',
            'duration' => 24,
            'gender' => 'female',
            'priceGross' => 15000,
            'description' => 'باقة استقدام خادمة منزلية من الفلبين لمدة 24 شهر',
            'createdAt' => now()->subDays(10)->toISOString(),
            'updatedAt' => now()->subDays(5)->toISOString(),
            'status' => 'available'
        ];

        return response()->json([
            'success' => true,
            'data' => $mockPackage
        ]);
    }

    /**
     * Show create contract form
     */
    public function createContract()
    {
        return view('admin.istiqdam.create-simple');
    }

    /**
     * Store new contract (mock)
     */
    public function storeContract(Request $request)
    {
        // Simple validation
        $request->validate([
            'beneficiary_id' => 'required|string',
            'beneficiary_birthdate' => 'required|date',
            'package_id' => 'required|integer',
            'contact_person_name' => 'required|string',
            'mobile_phone' => 'required|string',
            'email' => 'required|email',
        ]);

        // Mock successful response
        $mockContractId = 'IST-' . date('Ymd') . '-' . rand(100000, 999999);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء العقد التجريبي بنجاح',
            'data' => [
                'id' => $mockContractId,
                'status' => 'draft',
                'packageId' => $request->package_id,
                'beneficiaryId' => $request->beneficiary_id,
                'createdAt' => now()->toISOString()
            ],
            'contract_id' => $mockContractId
        ]);
    }

    /**
     * Get contract details (mock)
     */
    public function getContractDetails($contractId)
    {
        // Mock contract details
        $mockContract = [
            'id' => $contractId,
            'status' => 'active',
            'packageId' => 1001,
            'startDate' => now()->addDays(30)->toISOString(),
            'beneficiary' => [
                'id' => '1234567890',
                'birthdate' => '1985-05-15'
            ],
            'details' => [
                'beneficiary' => [
                    'contact' => [
                        'email' => 'test@example.com',
                        'person' => ['name' => 'أحمد محمد'],
                        'phoneNumber' => [
                            'mobile' => '+966501234567',
                            'home' => '+966112345678'
                        ]
                    ],
                    'address' => [
                        'city' => 'الرياض'
                    ]
                ],
                'payment' => [
                    'paymentMethod' => 'تحويل بنكي',
                    'discountPercentage' => 0
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $mockContract
        ]);
    }

    /**
     * Show simple test page
     */
    public function showTest()
    {
        return view('admin.istiqdam.test-simple');
    }
}
