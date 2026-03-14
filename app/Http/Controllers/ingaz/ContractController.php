<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\ingaz\User;
use App\Models\ingaz\Maid;
use App\Models\Contract;
use App\Models\ContractInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
// use Barryvdh\DomPDF\Facade as PDF;
use PDF;

class ContractController extends Controller
{
    /**
     * Show the create completed contract page
     */
    public function createCompletedPage()
    {
        return view('admin.ingaz.CompletedContract.create');
    }

    /**
     * Search clients by name or phone
     */
    public function searchClients(Request $request)
    {
        try {
            $search = $request->get('search', '');

            $clients = User::query()
                ->when($search, function($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->limit(20)
                ->get(['id', 'name', 'phone', 'national_num', 'birthdate']);

            $results = $clients->map(function($client) {
                return [
                    'id' => $client->id,
                    'text' => $client->name . ' - ' . $client->phone,
                    'national_num' => $client->national_num,
                    'birthdate' => $client->birthdate
                ];
            });

            return response()->json(['results' => $results]);

        } catch (\Exception $e) {
            return response()->json([
                'results' => [],
                'error' => 'حدث خطأ أثناء البحث عن العملاء'
            ], 500);
        }
    }

    /**
     * Search maids by name or passport
     */
    public function searchMaids(Request $request)
    {
        try {
            $search = $request->get('search', '');

            $maids = Maid::query()
                ->with('nationality')
                ->when($search, function($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('passport_num', 'like', "%{$search}%");
                    });
                })
                ->limit(20)
                ->get();

            $results = $maids->map(function($maid) {
                return [
                    'id' => $maid->id,
                    'text' => $maid->name . ' - ' . $maid->passport_num . ($maid->nationality ? ' - ' . $maid->nationality->name_ar : ''),
                    'maid' => [
                        'id' => $maid->id,
                        'name' => $maid->name,
                        'passport_num' => $maid->passport_num,
                        'nationality' => $maid->nationality ? $maid->nationality->name_ar : '',
                        'age' => $maid->age,
                        'salary' => $maid->salary,
                        'experience_years' => $maid->experience_years
                    ]
                ];
            });

            return response()->json(['maids' => $results]);

        } catch (\Exception $e) {
            return response()->json([
                'maids' => [],
                'error' => 'حدث خطأ أثناء البحث عن العمالة'
            ], 500);
        }
    }

    /**
     * Get all maids
     */
    public function getAllMaids()
    {
        try {
            $maids = Maid::with('nationality')->limit(50)->get();

            $results = $maids->map(function($maid) {
                return [
                    'id' => $maid->id,
                    'text' => $maid->name . ' - ' . $maid->passport_num,
                    'maid' => [
                        'id' => $maid->id,
                        'name' => $maid->name,
                        'passport_num' => $maid->passport_num,
                        'nationality' => $maid->nationality ? $maid->nationality->name_ar : '',
                        'age' => $maid->age,
                        'salary' => $maid->salary,
                        'experience_years' => $maid->experience_years
                    ]
                ];
            });

            return response()->json(['maids' => $results]);

        } catch (\Exception $e) {
            return response()->json([
                'maids' => [],
                'error' => 'حدث خطأ أثناء تحميل العمالة'
            ], 500);
        }
    }

    /**
     * Create a new contract
     */
    public function createContract(Request $request)
    {
        try {
            DB::beginTransaction();

            $clientType = $request->input('client_type');
            $clientId = null;

            // Handle client creation/selection
            if ($clientType === 'existing') {
                $clientId = $request->input('existing_client_id');
                if (!$clientId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يرجى اختيار عميل موجود'
                    ], 400);
                }
            } else {
                // Create new client
                $clientData = $request->validate([
                    'client_name' => 'required|string|max:255',
                    'client_phone' => 'required|string|max:20',
                    'client_email' => 'nullable|email',
                    'client_nationality_id' => 'nullable|exists:nationalities,id',
                    'client_national_num' => 'nullable|string|max:50',
                    'city' => 'nullable|string|max:100',
                    'address' => 'nullable|string'
                ]);

                $client = User::create([
                    'name' => $clientData['client_name'],
                    'phone' => $clientData['client_phone'],
                    'email' => $clientData['client_email'] ?? null,
                    'nationality_id' => $clientData['client_nationality_id'] ?? null,
                    'national_num' => $clientData['client_national_num'] ?? null,
                    'city' => $clientData['city'] ?? null,
                    'address' => $clientData['address'] ?? null,
                ]);

                $clientId = $client->id;
            }

            // Validate contract data
            $contractData = $request->validate([
                'maid_id' => 'required|exists:maids,id',
                'contract_date' => 'required|date',
                'contract_duration' => 'required|integer|min:1',
                'visa_number' => 'required|string|max:100',
                'msaned_number' => 'required|string|max:100',
                'tafwed_number' => 'nullable|string|max:100',
                'visa_milad_date' => 'nullable|date',
                'contract_status' => 'nullable|string|in:active,completed,cancelled'
            ]);

            // Check if maid is available
            $maid = Maid::find($contractData['maid_id']);
            if (!$maid) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة غير موجودة'
                ], 400);
            }

            if ($maid->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة غير متاحة حالياً'
                ], 400);
            }

            // Create contract
            $contract = Contract::create([
                'user_id' => $clientId,
                'maid_id' => $contractData['maid_id'],
                'contract_date' => $contractData['contract_date'],
                'contract_duration' => $contractData['contract_duration'],
                'visa_number' => $contractData['visa_number'],
                'msaned_number' => $contractData['msaned_number'],
                'tafwed_number' => $contractData['tafwed_number'] ?? null,
                'visa_milad_date' => $contractData['visa_milad_date'] ?? null,
                'status' => $contractData['contract_status'] ?? 'active',
                'desc' => $maid->name . ' - ' . $maid->passport_num
            ]);

            // Update maid status
            $maid->update(['status' => 'contracted']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد بنجاح',
                'contract' => $contract->load(['maid', 'user'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a completed contract for shelter
     */
    public function createCompletedContract(Request $request)
    {
        try {
            DB::beginTransaction();

            // Get existing client ID
            $clientId = $request->input('existing_client_id');
            if (!$clientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى اختيار عميل موجود'
                ], 400);
            }

            // Validate contract data
            $contractData = $request->validate([
                'maid_id' => 'required|exists:maids,id',
                'contract_date' => 'required|date',
                'arrival_date' => 'required|date',
                'visa_number' => 'required|string|max:100',
                'msaned_number' => 'nullable|string|max:100',
                'client_national_num' => 'nullable|string|max:50',
                'client_birthdate' => 'nullable|date'
            ]);

            // Check if maid is available
            $maid = Maid::find($contractData['maid_id']);
            if (!$maid) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة غير موجودة'
                ], 400);
            }

            // Update client information if provided
            $client = User::find($clientId);
            if ($client) {
                $client->update([
                    'national_num' => $contractData['client_national_num'] ?? $client->national_num,
                    'birthdate' => $contractData['client_birthdate'] ?? $client->birthdate,
                ]);
            }

            // Create completed contract
            $contract = Contract::create([
                'user_id' => $clientId,
                'maid_id' => $contractData['maid_id'],
                'status' => 'completed', // Set as completed (عقد مكتمل)
                'contract_date' => $contractData['contract_date'],
                'visa_number' => $contractData['visa_number'],
                'msaned_number' => $contractData['msaned_number'] ?? '',
            ]);

            // Create contract info with arrival date
            $contractInfo = ContractInfo::create([
                'contract_id' => $contract->id,
                'contract_date' => $contractData['contract_date'],
                'visa_number' => $contractData['visa_number'],
                'msaned_number' => $contractData['msaned_number'] ?? '',
                'arrival_date' => $contractData['arrival_date'],
            ]);

            // Update maid status to completed
            $maid->update(['status' => 'completed']);

            DB::commit();

            // Reload contract with relationships
            $contract->load(['maid', 'user', 'info']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد المكتمل بنجاح',
                'contract' => $contract,
                'redirect_url' => route('ShelterContract.create') . '?contract_id=' . $contract->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deliveryFormDownload($id)
    {
        $contract = Contract::with(['user', 'maid.nationality', 'info', 'marketer', 'travelHistory'])->findOrFail($id);

        $clientName = $contract->user->name ?? '';
        $clientNationalNumber = $contract->user->national_num ?? '';
        $clientPhone = trim(preg_replace('/\s+/', ' ', trim(($contract->user->phone_code ?? '') . ' ' . ($contract->user->phone ?? ''))));
        $clientAddress = $contract->user->address ?? '';

        $maid = $contract->maid;
        $maidName = $maid->name ?? '';
        $passportId = $maid->passport_num ?? '';
        $nationalityTitle = $maid?->nationality?->title ?? $maid?->nationality?->sub_title ?? '';

        $travelHistory = $contract->travelHistory()->latest('id')->first();
        $arrivalDateTime = $this->resolveArrivalDateTime($contract, $travelHistory);
        $receiptDateTime = $this->resolveReceiptDateTime($contract, $arrivalDateTime);

        $textReceipt = trim((string) ($contract->info?->receipt_form_text ?? $contract->text_receipt ?? ''));
        $companyRepresentative = $contract->marketer->name ?? auth('admin')->user()?->name ?? 'ممثل الشركة';
        $printedBy = auth('admin')->user()?->name ?? 'النظام';

        $viewData = [
            'contract' => $contract,
            'contract_number' => $contract->id,
            'client_name' => $clientName,
            'national_identification_number' => $clientNationalNumber,
            'client_phone' => $clientPhone,
            'client_address' => $clientAddress,
            'cvs_name' => $maidName,
            'passport_id' => $passportId,
            'nationality_id' => $nationalityTitle,
            'arrive_date' => $arrivalDateTime?->format('Y/m/d') ?? '--',
            'arrive_date_time' => $arrivalDateTime?->format('Y/m/d H:i') ?? '--',
            'date_receipt' => $receiptDateTime?->format('Y/m/d') ?? '--',
            'date_receipt_time' => $receiptDateTime?->format('Y/m/d H:i') ?? '--',
            'text_receipt' => $textReceipt,
            'company_representative' => $companyRepresentative,
            'printed_by' => $printedBy,
            'printed_at' => Carbon::now()->format('Y/m/d H:i:s'),
            'contract_signed_at' => optional($contract->created_at)->format('Y/m/d H:i') ?: '--',
            'preview_url' => route('contract_download_form.download', ['contract_id' => $contract->id]),
            'print_url' => route('contract_download_form.download', [
                'contract_id' => $contract->id,
                'print' => 1,
                'autoprint' => 1,
            ]),
            'pdf_url' => route('contract_download_form.download', [
                'contract_id' => $contract->id,
                'driver' => 'dompdf',
                'download' => 1,
            ]),
        ];

        if (request()->query('driver') !== 'dompdf') {
            if (request()->boolean('print')) {
                return response()->view('admin.ingaz.RecruitmentContract.delivery_form_printable', $viewData, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ]);
            }

            return response()->view('admin.ingaz.RecruitmentContract.delivery_form_print', $viewData, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        $pdfFilename = 'delivery-form-contract-' . $contract->id . '.pdf';
        $previousErrorReporting = error_reporting();

        error_reporting($previousErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            $pdf = PDF::loadView('admin.ingaz.RecruitmentContract.delivery_form', $viewData)->setPaper('a4', 'portrait');

            $pdf->setWarnings(false);
            $pdf->setOptions([
                'defaultFont' => 'dejavu sans',
                'isHtml5ParserEnabled' => true,
            ]);

            if (request()->boolean('download') || request()->query('type') === 'download') {
                return $pdf->download($pdfFilename);
            }

            return $pdf->stream($pdfFilename);
        } finally {
            error_reporting($previousErrorReporting);
        }
    }

    private function resolveArrivalDateTime(Contract $contract, $travelHistory): ?Carbon
    {
        if ($travelHistory) {
            if (filled($travelHistory->arrival_date) && filled($travelHistory->arrival_time)) {
                return $this->parseNullableDate($travelHistory->arrival_date . ' ' . $travelHistory->arrival_time);
            }

            if (filled($travelHistory->arrival_date)) {
                return $this->parseNullableDate($travelHistory->arrival_date);
            }

            if (filled($travelHistory->arrived_at ?? null)) {
                return $this->parseNullableDate($travelHistory->arrived_at);
            }
        }

        foreach ([
            $contract->warranty_start_date,
            $contract->info?->arrival_date ?? null,
            $contract->maid?->available_at ?? null,
        ] as $candidate) {
            $parsed = $this->parseNullableDate($candidate);
            if ($parsed) {
                return $parsed;
            }
        }

        return null;
    }

    private function resolveReceiptDateTime(Contract $contract, ?Carbon $arrivalDateTime): ?Carbon
    {
        foreach ([
            $contract->info?->receipt_form_date ?? null,
            $arrivalDateTime,
            $contract->warranty_start_date,
            $contract->contract_date,
            $contract->updated_at,
        ] as $candidate) {
            $parsed = $this->parseNullableDate($candidate);
            if ($parsed) {
                return $parsed;
            }
        }

        return null;
    }

    private function parseNullableDate($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return $value instanceof Carbon ? $value->copy() : Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
