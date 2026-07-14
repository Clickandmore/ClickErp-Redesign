<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\FinancialRequest;
use App\Models\Branch;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\RentalContract;
use App\Services\AdminNotificationService;
use App\Services\BranchAccessService;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\YearService;

class FinancialRequestController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $query = FinancialRequest::query()
            ->with(['creator', 'financeBond'])
            ->latest();

        if (! $this->canViewAll()) {
            $query->where('created_by_id', auth('admin')->id());
        }

        $this->applyRequestBranchFilter($query, $request, 'financial_requests');
        $counts = $this->requestScopeCounts($request);
        $this->applyFilters($query, $request);

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.ingaz.financial_requests.index', [
            'requests' => $requests,
            'types' => FinancialRequest::types(),
            'statuses' => FinancialRequest::statuses(),
            'priorities' => FinancialRequest::priorities(),
            'counts' => $counts,
            'scopes' => $this->requestScopes(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canCreate(), 403);

        return view('admin.ingaz.financial_requests.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreate(), 403);

        $data = $this->validatedData($request);

        DB::transaction(function () use ($request, $data) {
            $financialRequest = FinancialRequest::query()->create([
                ...$data,
                'request_no' => $this->nextRequestNo(),
                'status' => FinancialRequest::STATUS_FOR_APPROVAL,
                'created_by_id' => auth('admin')->id(),
                'updated_by_id' => auth('admin')->id(),
            ]);

            $this->storeAttachments($financialRequest, $request);
            $this->logStatus($financialRequest, null, FinancialRequest::STATUS_PREPARING, 'تم تجهيز الطلب.');
            $this->logStatus($financialRequest, FinancialRequest::STATUS_PREPARING, FinancialRequest::STATUS_FOR_APPROVAL, 'تم إرسال الطلب للاعتماد.');
        });

        return redirect()->route('financial-requests.index')->with('success', 'تم إنشاء الطلب المالي بنجاح.');
    }

    public function show(FinancialRequest $financialRequest)
    {
        abort_unless($this->canAccessRequest($financialRequest), 403);

        $financialRequest->load(['attachments', 'branch', 'creator', 'updater', 'approver', 'statusLogs.performer', 'financeBond.debitAccount', 'financeBond.creditAccount']);

        $workflowHistory = collect([[
            'status' => FinancialRequest::STATUS_PREPARING,
            'performer' => $financialRequest->creator?->name,
            'occurred_at' => $financialRequest->created_at,
            'remarks' => 'تم رفع الطلب المالي.',
            'inferred' => false,
        ]])->concat($financialRequest->statusLogs->map(fn ($log) => [
            'status' => $log->to_status,
            'performer' => $log->performer?->name,
            'occurred_at' => $log->created_at,
            'remarks' => $log->remarks,
            'inferred' => false,
        ]));

        if ($financialRequest->statusLogs->isEmpty() && $financialRequest->status !== FinancialRequest::STATUS_PREPARING) {
            $workflowHistory->push([
                'status' => $financialRequest->status,
                'performer' => $financialRequest->updater?->name,
                'occurred_at' => $financialRequest->updated_at,
                'remarks' => 'الحالة الحالية لطلب قديم لا يحتوي على سجل انتقالات محفوظ.',
                'inferred' => true,
            ]);
        }

        return view('admin.ingaz.financial_requests.show', [
            'requestItem' => $financialRequest,
            'workflowHistory' => $workflowHistory,
            'types' => FinancialRequest::types(),
            'statuses' => FinancialRequest::statuses(),
            'priorities' => FinancialRequest::priorities(),
            'contractTypes' => FinancialRequest::contractTypes(),
            'bondTypes' => $this->bondTypes(),
            'paymentTypes' => $this->paymentTypes(),
        ]);
    }

    public function edit(FinancialRequest $financialRequest)
    {
        abort_unless($this->canEdit($financialRequest), 403);

        return view('admin.ingaz.financial_requests.create', [
            ...$this->formData(),
            'requestItem' => $financialRequest->load('attachments'),
        ]);
    }

    public function update(Request $request, FinancialRequest $financialRequest)
    {
        abort_unless($this->canEdit($financialRequest), 403);

        $data = $this->validatedData($request);
        $data['updated_by_id'] = auth('admin')->id();
        $data['status'] = FinancialRequest::STATUS_FOR_APPROVAL;

        DB::transaction(function () use ($request, $financialRequest, $data) {
            $previousStatus = $financialRequest->status;
            $financialRequest->update($data);
            $this->storeAttachments($financialRequest, $request);
            $this->logStatus($financialRequest, $previousStatus, FinancialRequest::STATUS_FOR_APPROVAL, 'تم استكمال الطلب وإعادة إرساله للاعتماد.');
        });

        return redirect()->route('financial-requests.show', $financialRequest)->with('success', 'تم تحديث الطلب المالي.');
    }

    public function destroy(FinancialRequest $financialRequest)
    {
        abort_unless($this->canAccessRequest($financialRequest), 403);
        abort_unless(AdminGuardHasAnyPerm([(\App\PermKeys::FINANCIAL_REQUESTS_DELETE), \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]), 403);

        $financialRequest->delete();

        return redirect()->route('financial-requests.index')->with('success', 'تم حذف الطلب المالي.');
    }

    public function updateStatus(Request $request, FinancialRequest $financialRequest, BondService $bondService, YearService $yearService)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(FinancialRequest::statuses()))],
            'priority' => ['nullable', Rule::in(array_keys(FinancialRequest::priorities()))],
            'bond_type' => ['nullable', Rule::in([BondTypes::Qabd->value, BondTypes::Sarf->value])],
            'debit_account_id' => ['nullable', 'exists:fn_accounts,id'],
            'credit_account_id' => ['nullable', 'exists:fn_accounts,id'],
            'payment_type' => ['nullable', 'integer', Rule::in(array_map(fn (BondPaymentTypes $type) => $type->value, BondPaymentTypes::cases()))],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.*' => ['file', 'max:10240'],
        ]);

        abort_unless($this->canAccessRequest($financialRequest) && $this->canUpdateStatus($financialRequest, $data), 403);

        $previousStatus = $financialRequest->status;
        $previousPriority = $financialRequest->priority;

        if (($data['status'] === FinancialRequest::STATUS_PREPARING
                || ($data['status'] === FinancialRequest::STATUS_REQUEST_CLOSING && $previousStatus === FinancialRequest::STATUS_RECEIPT_ATTACHED))
            && trim((string) ($data['remarks'] ?? '')) === '') {
            return back()->withErrors(['remarks' => 'الملاحظات مطلوبة عند الرفض أو إعادة الطلب للاستكمال.']);
        }

        if ($data['status'] === FinancialRequest::STATUS_PAID && ! $this->hasStageAttachment($financialRequest, $request, 'payment_proof')) {
            return back()->withErrors(['attachments' => 'يجب إرفاق إثبات التحويل قبل تسجيل السداد.']);
        }

        if ($data['status'] === FinancialRequest::STATUS_RECEIPT_ATTACHED && ! $this->hasStageAttachment($financialRequest, $request, 'official_receipt')) {
            return back()->withErrors(['attachments' => 'يجب إرفاق الإيصال الرسمي قبل المتابعة.']);
        }

        DB::transaction(function () use ($request, $financialRequest, $data, $bondService, $yearService, $previousStatus) {
            $payload = [
                'status' => $data['status'],
                'priority' => $data['priority'] ?? $financialRequest->priority,
                'updated_by_id' => auth('admin')->id(),
            ];

            if ($data['status'] === FinancialRequest::STATUS_CLOSED && ! $financialRequest->finance_bond_id) {
                $this->createFinanceBond($financialRequest, $data, $bondService, $yearService);
                $financialRequest->refresh();
                $payload['closed_at'] = now();
            }

            if ($data['status'] === FinancialRequest::STATUS_PAID) {
                $payload['paid_at'] = now();
            }

            if ($data['status'] === FinancialRequest::STATUS_APPROVED) {
                $payload['approved_by_id'] = auth('admin')->id();
            }

            $financialRequest->update($payload);
            $this->storeAttachments($financialRequest, $request);
            $this->logStatus($financialRequest, $previousStatus, $data['status'], $data['remarks'] ?? null);
            $this->updateLinkedContract($financialRequest);
        });

        $financialRequest->refresh();
        $this->notifyFinancialRequestChanged($financialRequest, $previousStatus, $previousPriority);

        return back()->with('success', 'تم تحديث حالة الطلب المالي.');
    }

    public function searchClients(Request $request)
    {
        $term = trim((string) $request->input('q'));

        return User::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($nested) use ($term) {
                    $nested->where('name', 'like', "%{$term}%")
                        ->orWhere('national_num', 'like', "%{$term}%")
                        ->orWhere('tax_number', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->limit(15)
            ->get(['id', 'name', 'national_num', 'tax_number', 'phone'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'text' => trim($user->name . ' - ' . ($user->national_num ?: $user->tax_number)),
                'name' => $user->name,
                'identity' => $user->national_num ?: $user->tax_number,
            ]);
    }

    public function searchMaids(Request $request)
    {
        $term = trim((string) $request->input('q'));

        return Maid::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($nested) use ($term) {
                    $nested->where('name', 'like', "%{$term}%")
                        ->orWhere('passport_num', 'like', "%{$term}%");
                });
            })
            ->limit(15)
            ->get()
            ->map(fn (Maid $maid) => [
                'id' => $maid->id,
                'text' => trim(($maid->name ?? '-') . ' - ' . ($maid->passport_num ?? '')),
                'name' => $maid->name,
                'passport' => $maid->passport_num,
                'salary' => $maid->salary,
            ]);
    }

    public function searchAccounts(Request $request)
    {
        abort_unless(AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_EDIT, \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]), 403);

        $term = trim((string) $request->input('q'));

        $accounts = Account::query()
            ->whereDoesntHave('children')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($nested) use ($term) {
                    $nested->where('code', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->orderBy('code')
            ->limit(20)
            ->get(['id', 'code', 'name']);

        return response()->json([
            'results' => $accounts->map(fn (Account $account) => [
                'id' => $account->id,
                'text' => trim($account->code . ' - ' . $account->name),
            ])->values(),
        ]);
    }

    public function contractDetails(Request $request)
    {
        $number = trim((string) $request->input('contract_number'));
        $type = $request->input('contract_type');

        $contract = null;

        if ($type !== 'rental') {
            $contract = Contract::query()
                ->with(['user', 'maid'])
                ->where(function ($query) use ($number) {
                    $query->where('id', $number);

                    if (Schema::hasColumn('contracts', 'contract_number')) {
                        $query->orWhere('contract_number', $number);
                    }
                })
                ->first();
        }

        if (! $contract && $type !== 'recruitment') {
            $contract = RentalContract::query()
                ->with(['user', 'maid', 'info'])
                ->where('id', $number)
                ->orWhere('contract_number', $number)
                ->first();
        }

        if (! $contract) {
            return response()->json(['found' => false], 404);
        }

        $this->assertCanAccessBranchModel($contract);

        $isRental = $contract instanceof RentalContract;
        $info = $contract->info;
        $maid = $contract->maid;
        $client = $contract->user;

        return response()->json([
            'found' => true,
            'contract_id' => $contract->id,
            'contract_type' => $isRental ? 'rental' : 'recruitment',
            'contract_number' => $contract->contract_number ?? $contract->id,
            'contract_cost' => $isRental ? ($info->contract_value ?? null) : ($contract->contract_cost ?? null),
            'contract_salary' => $isRental ? ($info->monthly_salary ?? $maid?->salary) : ($maid?->salary),
            'client_id' => $client?->id,
            'client_name' => $client?->name,
            'client_identity' => $client?->national_num ?: $client?->tax_number,
            'maid_id' => $maid?->id,
            'maid_name' => $maid?->name,
            'maid_passport' => $maid?->passport_num,
        ]);
    }

    public function export(Request $request)
    {
        abort_unless(AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_EXPORT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]), 403);

        $query = FinancialRequest::query()->with('creator')->latest();
        $this->applyRequestBranchFilter($query, $request, 'financial_requests');
        $this->applyFilters($query, $request);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="financial-requests.csv"',
        ];

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم الطلب', 'النوع', 'الحالة', 'التصعيد', 'المبلغ', 'رقم العقد', 'هوية العميل', 'اسم العميل', 'مقدم الطلب', 'التاريخ']);

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->request_no,
                        FinancialRequest::types()[$row->type] ?? $row->type,
                        FinancialRequest::statuses()[$row->status] ?? $row->status,
                        FinancialRequest::priorities()[$row->priority] ?? $row->priority,
                        $row->amount,
                        $row->contract_number,
                        $row->client_identity,
                        $row->client_name,
                        $row->creator?->name,
                        optional($row->request_date)->format('Y-m-d'),
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    public function print(Request $request)
    {
        abort_unless(AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_EXPORT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]), 403);

        $query = FinancialRequest::query()->with('creator')->latest();
        $this->applyRequestBranchFilter($query, $request, 'financial_requests');
        $this->applyFilters($query, $request);

        return view('admin.ingaz.financial_requests.print', [
            'requests' => $query->get(),
            'types' => FinancialRequest::types(),
            'statuses' => FinancialRequest::statuses(),
            'priorities' => FinancialRequest::priorities(),
        ]);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(FinancialRequest::types()))],
            'priority' => ['required', Rule::in(array_keys(FinancialRequest::priorities()))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'request_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'client_id' => ['nullable', 'integer'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_identity' => ['nullable', 'string', 'max:255'],
            'maid_id' => ['nullable', 'integer'],
            'maid_name' => ['nullable', 'string', 'max:255'],
            'maid_passport' => ['nullable', 'string', 'max:255'],
            'contract_id' => ['nullable', 'integer'],
            'contract_type' => ['nullable', Rule::in(['recruitment', 'rental'])],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'contract_cost' => ['nullable', 'numeric', 'min:0'],
            'contract_salary' => ['nullable', 'numeric', 'min:0'],
            'payload' => ['nullable', 'array'],
            'payload.paid_to' => ['required', 'string', 'max:255'],
            'payload.accounting_name' => ['required', 'string', 'max:255'],
            'payload.debit_credit' => ['required', Rule::in(['debit', 'credit'])],
            'payload.payment_method' => ['required', Rule::in(['bank_transfer', 'cash'])],
            'payload.account_id' => ['nullable', 'integer', 'exists:fn_accounts,id'],
            'payload.bank_name' => ['nullable', 'string', 'max:255'],
            'payload.account_holder_name' => ['nullable', 'string', 'max:255'],
            'payload.account_number' => ['nullable', 'string', 'max:100'],
            'payload.iban' => ['nullable', 'string', 'max:34'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.*' => ['file', 'max:10240'],
        ]);

        $validated['payload'] = $validated['payload'] ?? [];

        $adminBranchId = (int) (auth('admin')->user()?->branch_id ?? 0);
        if (SystemModuleManager::isEnabled('branches') && $adminBranchId > 0 && (int) $validated['branch_id'] !== $adminBranchId) {
            abort(403, 'لا يمكن إنشاء طلب مالي لفرع آخر.');
        }

        return $validated;
    }

    private function formData(): array
    {
        return [
            'types' => FinancialRequest::types(),
            'typeFields' => FinancialRequest::typeFields(),
            'priorities' => FinancialRequest::priorities(),
            'contractTypes' => FinancialRequest::contractTypes(),
            'branches' => Branch::query()->active()
                ->when((int) (auth('admin')->user()?->branch_id ?? 0) > 0, fn ($query) => $query->whereKey(auth('admin')->user()->branch_id))
                ->ordered()->get(['id', 'name_ar', 'name_en']),
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->input('scope') === 'incomplete') {
            $query->whereIn('status', [
                FinancialRequest::STATUS_PREPARING,
                FinancialRequest::STATUS_FOR_APPROVAL,
                FinancialRequest::STATUS_APPROVED,
                FinancialRequest::STATUS_REQUEST_CLOSING,
                FinancialRequest::STATUS_RECEIPT_ATTACHED,
                FinancialRequest::STATUS_REJECTED,
            ]);
        } elseif ($request->input('scope') === 'paid') {
            $query->whereIn('status', $this->paidStatuses());
        }

        $query->when($request->filled('q'), function ($query) use ($request) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($nested) use ($term) {
                $nested->where('request_no', 'like', "%{$term}%")
                    ->orWhere('contract_number', 'like', "%{$term}%")
                    ->orWhere('client_identity', 'like', "%{$term}%")
                    ->orWhere('client_name', 'like', "%{$term}%");
            });
        });

        foreach (['type', 'status', 'priority', 'created_by_id'] as $field) {
            $query->when($request->filled($field), fn ($query) => $query->where($field, $request->input($field)));
        }

        $query->when($request->filled('date'), fn ($query) => $query->whereDate('request_date', $request->input('date')));
    }

    private function requestScopeCounts(Request $request): array
    {
        $query = FinancialRequest::query();

        if (! $this->canViewAll()) {
            $query->where('created_by_id', auth('admin')->id());
        }

        $this->applyRequestBranchFilter($query, $request, 'financial_requests');

        return [
            'all' => (clone $query)->count(),
            'incomplete' => (clone $query)->whereIn('status', [
                FinancialRequest::STATUS_PREPARING,
                FinancialRequest::STATUS_FOR_APPROVAL,
                FinancialRequest::STATUS_APPROVED,
                FinancialRequest::STATUS_REQUEST_CLOSING,
                FinancialRequest::STATUS_RECEIPT_ATTACHED,
                FinancialRequest::STATUS_REJECTED,
            ])->count(),
            'paid' => (clone $query)->whereIn('status', $this->paidStatuses())->count(),
        ];
    }

    private function requestScopes(): array
    {
        return trans('financial_requests.scopes');
    }

    private function paidStatuses(): array
    {
        return [
            FinancialRequest::STATUS_PAID,
            FinancialRequest::STATUS_REQUEST_CLOSING,
            FinancialRequest::STATUS_RECEIPT_ATTACHED,
            FinancialRequest::STATUS_CLOSED,
        ];
    }

    private function storeAttachments(FinancialRequest $financialRequest, Request $request): void
    {
        foreach ((array) $request->file('attachments', []) as $fieldKey => $files) {
            foreach ((array) $files as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                $path = $file->store("financial-requests/{$financialRequest->id}", 'public');
                $financialRequest->attachments()->create([
                    'field_key' => $fieldKey,
                    'label' => $request->input("attachment_labels.{$fieldKey}"),
                    'description' => $request->input("attachment_descriptions.{$fieldKey}"),
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }
    }

    private function createFinanceBond(FinancialRequest $financialRequest, array $data, BondService $bondService, YearService $yearService): void
    {
        if (! $financialRequest->amount || $financialRequest->amount <= 0) {
            throw new \RuntimeException('يجب تحديد مبلغ الطلب قبل السداد.');
        }

        foreach (['bond_type', 'debit_account_id', 'credit_account_id'] as $required) {
            if (empty($data[$required])) {
                throw new \RuntimeException('يجب تحديد نوع السند والحسابات عند تحويل الطلب إلى تم السداد.');
            }
        }

        $date = optional($financialRequest->request_date)->format('Y-m-d') ?: now()->toDateString();
        if (! $yearService->checkDateRange($date)) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ الطلب.');
        }

        $bondData = [
            'payment_type' => (int) ($data['payment_type'] ?? BondPaymentTypes::Cash->value),
            'bond_book_num' => $financialRequest->request_no,
            'debit_account_id' => (int) $data['debit_account_id'],
            'credit_account_id' => (int) $data['credit_account_id'],
            'value' => (float) $financialRequest->amount,
            'date' => $date,
            'statement' => 'طلب مالي رقم ' . $financialRequest->request_no . ' - ' . (FinancialRequest::types()[$financialRequest->type] ?? $financialRequest->type),
            'code' => $bondService->lastCode($data['bond_type']),
            'is_tax' => 0,
            'general_type' => 0,
        ];

        $bond = $data['bond_type'] === BondTypes::Sarf->value
            ? $bondService->storeSarfBond($bondData)
            : $bondService->storeQabdBond($bondData);

        $bondService->confirmBond($bond->id);

        $financialRequest->update([
            'finance_bond_id' => $bond->id,
            'finance_bond_type' => $data['bond_type'],
        ]);
    }

    private function updateLinkedContract(FinancialRequest $financialRequest): void
    {
        if ($financialRequest->status !== FinancialRequest::STATUS_PAID || ! $financialRequest->contract_id) {
            return;
        }

        if ($financialRequest->contract_type === 'rental') {
            RentalContract::query()->whereKey($financialRequest->contract_id)->update(['status' => 'active']);
            return;
        }

        if (Schema::hasColumn('contracts', 'financial_request_paid_at')) {
            Contract::query()->whereKey($financialRequest->contract_id)->update(['financial_request_paid_at' => now()]);
        }
    }

    private function nextRequestNo(): string
    {
        // request_no remains unique for soft-deleted rows. Include them when
        // calculating the next number and serialize concurrent generators.
        $next = ((int) FinancialRequest::withTrashed()
            ->lockForUpdate()
            ->max('id')) + 1;

        return 'FR-' . now()->format('Ymd') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function bondTypes(): array
    {
        return [
            BondTypes::Qabd->value => 'سند قبض',
            BondTypes::Sarf->value => 'سند صرف',
        ];
    }

    private function paymentTypes(): array
    {
        return collect(BondPaymentTypes::cases())
            ->mapWithKeys(fn (BondPaymentTypes $type) => [$type->value => $type->lang()])
            ->all();
    }

    private function notifyFinancialRequestChanged(FinancialRequest $financialRequest, ?string $previousStatus, ?string $previousPriority): void
    {
        $statusChanged = (string) $previousStatus !== (string) $financialRequest->status;
        $priorityChanged = (string) $previousPriority !== (string) $financialRequest->priority;

        if (!$statusChanged && !$priorityChanged) {
            return;
        }

        $changes = [];
        if ($statusChanged) {
            $changes[] = 'الحالة من "' . FinancialRequest::labelFromMap(FinancialRequest::statuses(), $previousStatus) . '" إلى "' . $financialRequest->status_label . '"';
        }

        if ($priorityChanged) {
            $changes[] = 'التصعيد من "' . FinancialRequest::labelFromMap(FinancialRequest::priorities(), $previousPriority) . '" إلى "' . $financialRequest->priority_label . '"';
        }

        app(AdminNotificationService::class)->sendToAdmin(
            $financialRequest->created_by_id,
            'تم تحديث طلبك المالي ' . ($financialRequest->request_no ?: '#' . $financialRequest->id),
            'تم تغيير ' . implode(' و', $changes) . '.',
            'financial_request_updated',
            route('financial-requests.show', $financialRequest),
            [
                'model' => FinancialRequest::class,
                'model_id' => $financialRequest->id,
                'old_status' => $previousStatus,
                'new_status' => $financialRequest->status,
                'old_priority' => $previousPriority,
                'new_priority' => $financialRequest->priority,
            ]
        );
    }

    private function canView(): bool
    {
        return AdminGuardHasAnyPerm([
            \App\PermKeys::FINANCIAL_REQUESTS_VIEW,
            \App\PermKeys::FINANCIAL_REQUESTS_CREATE,
            \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS,
            \App\PermKeys::FINANCIAL_REQUESTS_APPROVE,
            \App\PermKeys::FINANCIAL_REQUESTS_CONTROL,
        ]);
    }

    private function canViewAll(): bool
    {
        return AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
    }

    private function canCreate(): bool
    {
        return AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
    }

    private function canEdit(FinancialRequest $financialRequest): bool
    {
        return $this->canAccessRequest($financialRequest)
            && in_array($financialRequest->status, [FinancialRequest::STATUS_PREPARING, FinancialRequest::STATUS_REJECTED], true)
            && (int) $financialRequest->created_by_id === (int) auth('admin')->id()
            && AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_EDIT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
    }

    private function canAccessRequest(FinancialRequest $financialRequest): bool
    {
        if (! app(BranchAccessService::class)->canAccessModel($financialRequest)) {
            return false;
        }

        return $this->canViewAll()
            || (AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_VIEW, \App\PermKeys::FINANCIAL_REQUESTS_CREATE]) && (int) $financialRequest->created_by_id === (int) auth('admin')->id());
    }

    private function canUpdateStatus(FinancialRequest $financialRequest, array $data): bool
    {
        $nextStatus = $data['status'] ?? $financialRequest->status;
        $nextPriority = $data['priority'] ?? $financialRequest->priority;

        $allowed = [
            FinancialRequest::STATUS_FOR_APPROVAL => [FinancialRequest::STATUS_APPROVED, FinancialRequest::STATUS_PREPARING],
            FinancialRequest::STATUS_PREPARING => [FinancialRequest::STATUS_FOR_APPROVAL],
            FinancialRequest::STATUS_REJECTED => [FinancialRequest::STATUS_FOR_APPROVAL],
            FinancialRequest::STATUS_APPROVED => [FinancialRequest::STATUS_PAID],
            FinancialRequest::STATUS_PAID => [FinancialRequest::STATUS_REQUEST_CLOSING],
            FinancialRequest::STATUS_REQUEST_CLOSING => [FinancialRequest::STATUS_RECEIPT_ATTACHED],
            FinancialRequest::STATUS_RECEIPT_ATTACHED => [FinancialRequest::STATUS_CLOSED, FinancialRequest::STATUS_REQUEST_CLOSING],
        ];

        if ($nextStatus !== $financialRequest->status && ! in_array($nextStatus, $allowed[$financialRequest->status] ?? [], true)) {
            return false;
        }

        $creatorActions = [
            FinancialRequest::STATUS_PREPARING => FinancialRequest::STATUS_FOR_APPROVAL,
            FinancialRequest::STATUS_REJECTED => FinancialRequest::STATUS_FOR_APPROVAL,
            FinancialRequest::STATUS_APPROVED => FinancialRequest::STATUS_PAID,
            FinancialRequest::STATUS_REQUEST_CLOSING => FinancialRequest::STATUS_RECEIPT_ATTACHED,
        ];
        if (($creatorActions[$financialRequest->status] ?? null) === $nextStatus) {
            return (int) $financialRequest->created_by_id === (int) auth('admin')->id();
        }

        if ($nextStatus === FinancialRequest::STATUS_PAID && $financialRequest->status !== FinancialRequest::STATUS_PAID) {
            return (int) $financialRequest->created_by_id === (int) auth('admin')->id();
        }

        if ($nextStatus !== $financialRequest->status) {
            return AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
        }

        if ($nextPriority !== $financialRequest->priority) {
            return AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_ESCALATE, \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
        }

        return AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_ESCALATE, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
    }

    private function logStatus(FinancialRequest $financialRequest, ?string $from, string $to, ?string $remarks = null): void
    {
        $financialRequest->statusLogs()->create([
            'from_status' => $from,
            'to_status' => $to,
            'performed_by_id' => auth('admin')->id(),
            'remarks' => $remarks,
        ]);
    }

    private function hasStageAttachment(FinancialRequest $financialRequest, Request $request, string $key): bool
    {
        return count((array) $request->file("attachments.{$key}", [])) > 0
            || $financialRequest->attachments()->where('field_key', $key)->exists();
    }
}
