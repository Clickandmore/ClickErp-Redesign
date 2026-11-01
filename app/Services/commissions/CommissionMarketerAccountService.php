<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\AccountService;

class CommissionMarketerAccountService
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly LegacyCommissionMarketerResolver $nameResolver
    ) {}

    public function parentAccount(): Account
    {
        $parent = Account::query()->where('code', \Nami\Finance\Support\AccountCodeAliases::current(144))->first();
        if (!$parent) {
            $parents = Account::query()->where('name', 'المسوقين')->get();
            $parent = $parents->count() === 1 ? $parents->first() : null;
        }
        if (!$parent || ($parent->name !== 'المسوقين' && $parent->type !== AccountTypesEnum::Marketers)) {
            throw ValidationException::withMessages([
                'accrual' => 'تعذر تحديد الحساب الرئيسي للمسوقين. راجع حساب «المسوقين» في شجرة الحسابات.',
            ]);
        }

        return $parent;
    }

    public function payableAccounts(): Builder
    {
        return Account::query()
            ->where('parent_id', $this->parentAccount()->id)
            ->where('status', 'creditor')
            ->whereDoesntHave('children');
    }

    public function resolve(int $marketerId, ?int $actorId = null): Account
    {
        return DB::transaction(function () use ($marketerId, $actorId) {
            // Serialize allocation for all marketers under this parent, including account-code generation.
            $parent = $this->parentAccount();
            Account::query()->whereKey($parent->id)->lockForUpdate()->firstOrFail();
            $marketer = Admin::withTrashed()->whereKey($marketerId)->lockForUpdate()->firstOrFail();
            $query = $this->payableAccounts();

            if ($marketer->commission_payable_account_id) {
                $account = (clone $query)->find($marketer->commission_payable_account_id);
                if (!$account || $this->belongsToAnotherMarketer($account, $marketerId)) {
                    $this->invalidAccount($marketer);
                }

                return $account;
            }

            // Preserve the ledger account already used by this marketer, even after a name change.
            $historicalIds = CommissionBatch::query()->where('marketer_id', $marketerId)
                ->whereNotNull('accrual_constraint_id')->whereNotNull('payable_account_id')
                ->distinct()->pluck('payable_account_id');
            if ($historicalIds->count() > 1) {
                $this->invalidAccount($marketer);
            }
            if ($historicalIds->isNotEmpty()) {
                $account = (clone $query)->find($historicalIds->first());
                if (!$account || $this->belongsToAnotherMarketer($account, $marketerId)) {
                    $this->invalidAccount($marketer);
                }
            } else {
                $names = collect([$marketer->name, $this->nameResolver->displayName($marketer)])
                    ->map(fn ($name) => $this->normalizeName((string) $name))->filter()->unique();
                $matches = (clone $query)->get()->filter(
                    fn (Account $candidate) => $names->contains($this->normalizeName($candidate->name))
                );
                if ($matches->count() > 1) {
                    $this->invalidAccount($marketer);
                }
                $account = $matches->first();
                if ($account && $this->belongsToAnotherMarketer($account, $marketerId)) {
                    $this->invalidAccount($marketer);
                }
                if (!$account) {
                    $actorId ??= auth('admin')->id();
                    if (!$actorId) {
                        throw ValidationException::withMessages(['accrual' => 'يجب تحديد المستخدم الذي يعتمد العمولة لإنشاء حساب المسوق.']);
                    }
                    $account = $this->accountService->storeAccount([
                        'name' => trim($marketer->name) ?: 'مسوق #' . $marketerId,
                        'parent_id' => $parent->id,
                        'status' => 'creditor',
                        'type' => AccountTypesEnum::Marketer->value,
                        'created_by_id' => $actorId,
                        'branch_id' => $marketer->branch_id,
                    ]);
                }
            }

            $marketer->update(['commission_payable_account_id' => $account->id]);

            return $account;
        });
    }

    public function belongsToAnotherMarketer(Account $account, int $marketerId): bool
    {
        return Admin::withTrashed()->where('id', '<>', $marketerId)
            ->where('commission_payable_account_id', $account->id)->exists()
            || CommissionBatch::query()->where('marketer_id', '<>', $marketerId)
                ->where('payable_account_id', $account->id)->whereNotNull('accrual_constraint_id')->exists();
    }

    private function normalizeName(string $name): string
    {
        return $this->nameResolver->normalize(preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $name));
    }

    private function invalidAccount(Admin $marketer): never
    {
        throw ValidationException::withMessages([
            'accrual' => "تعذر تحديد حساب استحقاق مستقل وصالح للمسوق «{$marketer->name}». راجع ربط حسابه في إعدادات العمولات؛ يجب أن يكون حسابًا دائنًا نهائيًا تحت «المسوقين» وغير مرتبط بمسوق آخر.",
        ]);
    }
}
