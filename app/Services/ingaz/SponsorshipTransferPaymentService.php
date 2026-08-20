<?php

namespace App\Services\ingaz;

use App\Models\SponsorshipTransfer;
use App\Models\ingaz\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\YearService;

class SponsorshipTransferPaymentService
{
    public function __construct(
        protected BondService $bondService,
        protected YearService $yearService
    ) {
    }

    public static function receiptAccounts()
    {
        return Account::query()
            ->where('status', 'debtor')
            ->whereIn('type', [
                AccountTypesEnum::Bank->value,
                AccountTypesEnum::Box->value,
                AccountTypesEnum::ElectronicWallet->value,
            ])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }

    public function createReceipt(SponsorshipTransfer $transfer, int $receiptAccountId, Carbon|string|null $date = null): array
    {
        if ($transfer->isRentalWorkflow()) {
            return ['bond' => null, 'constraint' => null];
        }

        $transfer->loadMissing(['newSponsor', 'contract.maid', 'receiptBond']);

        if ((float) $transfer->paid_amount <= 0) {
            return ['bond' => null, 'constraint' => null];
        }

        if ($transfer->receipt_bond_id) {
            return ['bond' => $transfer->receiptBond, 'constraint' => $transfer->receiptBond?->constraint];
        }

        $receiptAccount = $this->resolveReceiptAccount($receiptAccountId);
        $clientAccountId = $this->ensureClientAccountId($transfer->newSponsor);
        $paymentDate = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : now()->toDateString());

        if (!$this->yearService->checkDateRange($paymentDate)) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ سند القبض المحدد.');
        }

        $bond = $this->bondService->storeQabdBond([
            'payment_type' => $this->mapPaymentType((string) $transfer->payment_type)->value,
            'bond_book_num' => ' ',
            'debit_account_id' => $receiptAccount->id,
            'credit_account_id' => $clientAccountId,
            'value' => round((float) $transfer->paid_amount, 2),
            'date' => $paymentDate,
            'statement' => 'سند قبض طلب نقل كفالة رقم ' . $transfer->id
                . ' / العميل ' . ($transfer->newSponsor?->name ?: 'غير محدد')
                . ' / العاملة ' . ($transfer->contract?->maid?->name ?: 'غير محدد')
                . ' / إلى حساب ' . $receiptAccount->name,
            'code' => $this->bondService->lastCode(BondTypes::Qabd->value),
            'is_tax' => 0,
            'general_type' => 0,
            'sponsorship_transfer_id' => $transfer->id,
        ])->fresh();

        if ((int) $bond->is_confirm !== 1 || !$bond->constraint_id) {
            $this->bondService->confirmBond($bond->id);
            $bond = $bond->fresh();
        }

        $transfer->update([
            'receipt_account_id' => $receiptAccount->id,
            'receipt_bond_id' => $bond->id,
            'receipt_constraint_id' => $bond->constraint_id,
        ]);

        return ['bond' => $bond, 'constraint' => $bond->constraint];
    }

    public function createRefund(SponsorshipTransfer $transfer, float $amount, int $paymentAccountId, Carbon|string|null $date = null): array
    {
        if ($transfer->isRentalWorkflow()) {
            return ['bond' => null, 'constraint' => null];
        }

        $transfer->loadMissing(['newSponsor', 'contract.maid', 'refundBond']);
        if ($amount <= 0) {
            return ['bond' => null, 'constraint' => null];
        }
        if ($transfer->refund_bond_id) {
            return ['bond' => $transfer->refundBond, 'constraint' => $transfer->refundBond?->constraint];
        }

        $paymentAccount = $this->resolveReceiptAccount($paymentAccountId);
        $clientAccountId = $this->ensureClientAccountId($transfer->newSponsor);
        $paymentDate = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : now()->toDateString());
        if (!$this->yearService->checkDateRange($paymentDate)) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ سند الصرف المحدد.');
        }

        $bond = $this->bondService->storeSarfBond([
            'payment_type' => $this->mapAccountPaymentType($paymentAccount)->value,
            'bond_book_num' => ' ',
            'debit_account_id' => $clientAccountId,
            'credit_account_id' => $paymentAccount->id,
            'value' => round($amount, 2),
            'date' => $paymentDate,
            'statement' => 'سند صرف استرجاع طلب نقل كفالة رقم ' . $transfer->id
                . ' / العميل ' . ($transfer->newSponsor?->name ?: 'غير محدد')
                . ' / من حساب ' . $paymentAccount->name,
            'code' => $this->bondService->lastCode(BondTypes::Sarf->value),
            'is_tax' => 0,
            'general_type' => 0,
            'sponsorship_transfer_id' => $transfer->id,
        ])->fresh();

        if ((int) $bond->is_confirm !== 1 || !$bond->constraint_id) {
            $this->bondService->confirmBond($bond->id);
            $bond = $bond->fresh();
        }

        $transfer->update([
            'refund_account_id' => $paymentAccount->id,
            'refund_bond_id' => $bond->id,
            'refund_constraint_id' => $bond->constraint_id,
        ]);

        return ['bond' => $bond, 'constraint' => $bond->constraint];
    }

    private function resolveReceiptAccount(int $accountId): Account
    {
        $account = Account::query()->find($accountId);
        $allowedTypes = [
            AccountTypesEnum::Bank->value,
            AccountTypesEnum::Box->value,
            AccountTypesEnum::ElectronicWallet->value,
        ];

        if (!$account || $account->status?->value !== 'debtor'
            || !in_array((int) $account->getRawOriginal('type'), $allowedTypes, true)) {
            throw new \RuntimeException('حساب السداد يجب أن يكون حساب بنك أو صندوق أو محفظة إلكترونية صالحاً.');
        }

        return $account;
    }

    private function ensureClientAccountId(?User $user): int
    {
        if (!$user) {
            throw new \RuntimeException('العميل الجديد غير موجود لإنشاء سند القبض.');
        }

        $user = User::query()->lockForUpdate()->findOrFail($user->id);
        if ($user->account_id && Account::query()->whereKey($user->account_id)->exists()) {
            return (int) $user->account_id;
        }

        $parent = Account::query()
            ->where('type', AccountTypesEnum::Clients->value)
            ->lockForUpdate()
            ->first();
        if (!$parent) {
            throw new \RuntimeException('لا يوجد حساب رئيسي للعملاء في الدليل المحاسبي.');
        }

        $lastCode = Account::query()->where('parent_id', $parent->id)->max('code')
            ?: (int) ($parent->code . '00000');
        $account = Account::query()->create([
            'name' => $user->name ?: ('عميل #' . $user->id),
            'operation_type' => $parent->operation_type,
            'status' => $parent->status,
            'statement_type' => $parent->statement_type,
            'level' => $parent->level + 1,
            'type' => AccountTypesEnum::Client->value,
            'parent_id' => $parent->id,
            'code' => ((int) $lastCode) + 1,
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
        $user->update(['account_id' => $account->id]);

        return (int) $account->id;
    }

    private function mapPaymentType(string $paymentType): BondPaymentTypes
    {
        return match ($paymentType) {
            'cash' => BondPaymentTypes::Cash,
            'mada' => BondPaymentTypes::Network,
            'tabby', 'tamara' => BondPaymentTypes::ElectronicWallet,
            default => BondPaymentTypes::BankTransfer,
        };
    }

    private function mapAccountPaymentType(Account $account): BondPaymentTypes
    {
        return match ((int) $account->getRawOriginal('type')) {
            AccountTypesEnum::Box->value => BondPaymentTypes::Cash,
            AccountTypesEnum::ElectronicWallet->value => BondPaymentTypes::ElectronicWallet,
            default => BondPaymentTypes::BankTransfer,
        };
    }
}
