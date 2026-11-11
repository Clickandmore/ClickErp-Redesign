<?php

namespace Nami\Finance\Http\Requests;

use App\Services\BranchAccessService;
use App\Http\Requests\MainRequest;
use App\Support\BranchSharingSettings;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;

class ConstraintRequest extends MainRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->normalizeNumericValue($this->input('code')),
            'value' => $this->normalizeNumericValue($this->input('value')),
            'debtor_value' => $this->normalizeNumericValue($this->input('debtor_value', [])),
            'creditor_value' => $this->normalizeNumericValue($this->input('creditor_value', [])),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            default => $this->view()
        };

    }

    protected function store(): array
    {
        $branchAccess = app(BranchAccessService::class);
        $admin = auth('admin')->user();
        $branchMustBeSelected = $branchAccess->enabled()
            && ($branchAccess->adminBranchId($admin) === null || $branchAccess->canViewAllBranches($admin));

        return [
            'branch_id' => [Rule::requiredIf($branchMustBeSelected), 'nullable', 'integer', 'exists:branches,id'],
            'detail_branch_id' => 'nullable|array',
            'detail_branch_id.*' => 'nullable|integer|exists:branches,id',
            'type' => 'nullable',
            'value' => 'required|numeric|gt:0',
            'date' => 'required|date_format:Y-m-d',
            'statement' => 'nullable|string',
            'account_id' => 'required|array|min:2',
            'account_id.*' => 'required|exists:fn_accounts,id',
            'debtor_value' => 'required|array|min:1',
            'debtor_value.*' => 'nullable|numeric|min:0',
            'creditor_value' => 'required|array|min:1',
            'creditor_value.*' => 'nullable|numeric|min:0',
            'code' => 'required|numeric',
            'cost_center_required' => 'nullable',
            'cost_center_id' => 'nullable|array',
            'cost_center_id.*' => 'nullable|required_if:cost_center_required,on|exists:fn_cost_centers,id',
            'file' => 'nullable|array',
            'file.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv|max:10240',
            'statement_account' => 'nullable|array',
            'statement_account.*' => 'nullable|string',
            'account_type' => 'required|array|min:2',
            'account_type.*' => 'required|in:debtor,creditor',
        ];
    }

    protected function update(): array
    {
        return [
            'type' => 'nullable',
            'value' => 'sometimes|nullable|numeric|gt:0',
            'date' => 'required|date_format:Y-m-d',
            'statement' => 'nullable|string',
            'account_id' => 'required|array|min:2',
            'account_id.*' => 'required|exists:fn_accounts,id',
            'debtor_value' => 'required|array|min:1',
            'debtor_value.*' => 'nullable|numeric|min:0',
            'old_id' => 'nullable|array',
            'old_id.*' => 'nullable|exists:fn_constraint_details,id',
            'detail_branch_id' => 'nullable|array',
            'detail_branch_id.*' => 'nullable|integer|exists:branches,id',
            'creditor_value' => 'required|array|min:1',
            'creditor_value.*' => 'nullable|numeric|min:0',
            'cost_center_required' => 'nullable',
            'cost_center_id' => 'nullable|array',
            'cost_center_id.*' => 'nullable|required_if:cost_center_required,on|exists:fn_cost_centers,id',
            'file' => 'nullable|array',
            'file.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv|max:10240',
            'statement_account' => 'nullable|array',
            'statement_account.*' => 'nullable|string',
            'account_type' => 'required|array|min:2',
            'account_type.*' => 'required|in:debtor,creditor',
        ];
    }

    protected function destroy(): array
    {
        return [];
    }

    protected function view(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'date.required' => __('finance::finance.constraint_date_required'),
            'date.date_format' => __('finance::finance.constraint_date_invalid'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $debtorTotal = $this->sumAmounts($this->input('debtor_value', []));
            $creditorTotal = $this->sumAmounts($this->input('creditor_value', []));

            if ($debtorTotal <= 0 || round($debtorTotal, 2) !== round($creditorTotal, 2)) {
                $validator->errors()->add('value', __('finance::finance.debit_credit_totals_must_match'));
            }

            $branchAccess = app(BranchAccessService::class);
            if (! $branchAccess->enabled()) {
                return;
            }

            $headerBranchId = $this->branchIdForAccountValidation($branchAccess);
            $lineBranchIds = (array) $this->input('detail_branch_id', []);

            // فرع السطر: المستخدم يختار فقط من الفروع المسموح له بها. عند
            // التعديل نسمح لفرع غير متاح بالبقاء على السطر القديم نفسه فقط؛
            // ولا يصبح هذا الفرع خياراً لسطر آخر أو لسطر جديد.
            $allowedBranchIds = $this->allowedLineBranchIds($branchAccess);
            $existingLineBranches = $this->existingLineBranches();
            $oldIds = (array) $this->input('old_id', []);
            $effectiveLineBranches = [];
            $rejected = collect((array) $this->input('account_id', []))->filter(function ($accountId, $key) use (
                $allowedBranchIds,
                $existingLineBranches,
                $headerBranchId,
                $lineBranchIds,
                $oldIds,
                &$effectiveLineBranches
            ) {
                $submittedBranchId = (int) ($lineBranchIds[$key] ?? 0);
                $oldId = (int) ($oldIds[$key] ?? 0);
                $branchId = $submittedBranchId > 0
                    ? $submittedBranchId
                    : ($existingLineBranches[$oldId] ?? (int) ($headerBranchId ?? 0));
                $effectiveLineBranches[$key] = $branchId;

                if ($branchId <= 0 || in_array($branchId, $allowedBranchIds, true)) {
                    return false;
                }

                return $oldId <= 0
                    || ($existingLineBranches[$oldId] ?? null) !== $branchId;
            })->unique();
            if ($rejected->isNotEmpty()) {
                $validator->errors()->add('detail_branch_id', 'لا تملك صلاحية تسجيل أسطر على هذا الفرع.');
            }

            if (! BranchSharingSettings::accountsPerBranch()) {
                return;
            }

            // كل سطر يُفحص حسابه مقابل فرعه هو، وإلا فرع رأس القيد.
            $accountsByBranch = [];
            foreach ((array) $this->input('account_id', []) as $key => $accountId) {
                $branchId = (int) ($effectiveLineBranches[$key] ?? 0);
                if ($branchId > 0 && (int) $accountId > 0) {
                    $accountsByBranch[$branchId][] = (int) $accountId;
                }
            }

            foreach ($accountsByBranch as $branchId => $accountIds) {
                $invalid = Account::query()
                    ->whereIn('id', $accountIds)
                    ->whereHas('branches')
                    ->whereDoesntHave('branches', fn ($branches) => $branches->whereKey($branchId))
                    ->pluck('code');

                if ($invalid->isNotEmpty()) {
                    $validator->errors()->add(
                        'account_id',
                        'حسابات غير متاحة لفرع السطر: ' . $invalid->implode(', ')
                    );
                }
            }
        });
    }

    /** @return array<int, int> */
    private function allowedLineBranchIds(BranchAccessService $branchAccess): array
    {
        return $branchAccess->selectorBranches(auth('admin')->user())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array<int, int> [detail id => branch id] */
    private function existingLineBranches(): array
    {
        $constraintId = in_array($this->method(), ['PUT', 'PATCH'], true) ? $this->routeConstraintId() : 0;
        if ($constraintId <= 0) {
            return [];
        }

        return DB::table('fn_constraint_details')
            ->where('constraint_id', $constraintId)
            ->whereNull('deleted_at')
            ->whereNotNull('branch_id')
            ->pluck('branch_id', 'id')
            ->mapWithKeys(fn ($branchId, $id) => [(int) $id => (int) $branchId])
            ->all();
    }

    private function branchIdForAccountValidation(BranchAccessService $branchAccess): ?int
    {
        if ($this->method() === 'POST') {
            return $branchAccess->selectedBranchId($this);
        }

        if (! in_array($this->method(), ['PUT', 'PATCH'], true)) {
            return null;
        }

        $constraintId = $this->routeConstraintId();
        if ($constraintId <= 0) {
            return null;
        }

        $branchId = (int) (DB::table('fn_constraints')
            ->where('id', $constraintId)
            ->value('branch_id') ?? 0);

        return $branchId > 0 ? $branchId : null;
    }

    private function routeConstraintId(): int
    {
        $routeValue = $this->route('add_constraint')
            ?? $this->route('add-constraint')
            ?? $this->route('constraint')
            ?? $this->route('id');

        if (is_object($routeValue) && method_exists($routeValue, 'getKey')) {
            return (int) $routeValue->getKey();
        }

        return (int) $routeValue;
    }

    private function normalizeNumericValue($value)
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeNumericValue($item), $value);
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫', '٬', '،'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '', '.'],
            $value
        );

        if (substr_count($value, ',') === 1 && !str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        return str_replace(' ', '', $value);
    }

    private function sumAmounts(array $amounts): float
    {
        return collect($amounts)
            ->map(fn ($amount) => (float) $this->normalizeNumericValue($amount))
            ->sum();
    }
}
