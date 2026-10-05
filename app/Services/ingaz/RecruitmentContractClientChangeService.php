<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\ingaz\User;
use App\Services\BranchAccessService;
use App\Services\BranchDataSharingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RecruitmentContractClientChangeService
{
    /** All checks deliberately include draft and archived finance records. */
    private const FINANCIAL_LINKS = [
        'invoices' => 'فاتورة',
        'contract_payments' => 'دفعة',
        'musaned_transfer_items' => 'تحويل مساند',
        'recruitment_contract_refunds' => 'استرداد',
        'recruitment_invoice_credit_notes' => 'إشعار دائن',
        'contract_return_settlements' => 'تسوية استرداد',
        'financial_requests' => 'طلب مالي',
        'fn_procurement_documents' => 'مستند مشتريات',
        'shelter_cost_entries' => 'تكلفة إيواء',
        'shelter_agent_cost_recoveries' => 'استرداد تكلفة',
        'shelter_living_expense_allocations' => 'توزيع مصروفات إيواء',
        'commission_batch_items' => 'عمولة',
        'arabia_recruitment_financial_references' => 'مرجع مالي مستورد',
    ];

    public function eligibleClientsQuery()
    {
        $query = User::query()->where('is_blocked', 0);
        if (Schema::hasColumn('users', 'archived_company_profile_id')) {
            $query->whereNull('archived_company_profile_id');
        }
        $access = app(BranchAccessService::class);
        if ($access->enabled()
            && !@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_VIEW_ALL, \App\PermKeys::CLIENTS_VIEW_ALL_BRANCHES])) {
            $branchId = $access->adminBranchId();
            if (!$branchId) {
                $query->whereRaw('1 = 0');
            } else {
                $access->applyBranchIdToQuery($query, $branchId, 'users', null,
                    app(BranchDataSharingService::class)->sharedSourceBranchIds(BranchDataSharingService::CLIENTS));
            }
        }
        return $query;
    }

    public function blockingReason(Contract $contract, bool $lock = false): ?string
    {
        if ($contract->company_profile_id) {
            return 'هذا العقد مملوك للشركة، ولا يمكن تحويله إلى عميل من هذا الإجراء.';
        }
        $contractId = (int) $contract->id;
        foreach (self::FINANCIAL_LINKS as $table => $label) {
            if (!Schema::hasTable($table)) continue;
            $query = DB::table($table)->where('contract_id', $contractId);
            if ($table === 'financial_requests') {
                $query->where(function ($query) {
                    $query->where('contract_type', 'recruitment')->orWhereNull('contract_type');
                });
            }
            if ($table === 'commission_batch_items' && Schema::hasColumn($table, 'source_type')) {
                $query = DB::table($table)->where(function ($query) use ($contractId, $contract) {
                    $query->where('contract_id', $contractId)->orWhere(function ($query) use ($contractId) {
                        $query->where('source_type', \App\Services\commissions\CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
                            ->where('source_id', $contractId);
                    });
                    if ($contract->order_id && Schema::hasColumn('commission_batch_items', 'order_id')) {
                        $query->orWhere('order_id', $contract->order_id);
                    }
                });
            }
            if ($lock) $query->lockForUpdate();
            if ($query->first(['id'])) {
                return 'لا يمكن تغيير العميل لوجود ' . $label . ' مرتبطة بالعقد، حتى لو لم يتم السداد.';
            }
        }
        foreach (['fn_bonds' => 'سند مالي', 'fn_constraints' => 'قيد مالي', 'fn_constraint_details' => 'قيد مالي'] as $table => $label) {
            if (!Schema::hasTable($table)) continue;
            if ($table === 'fn_bonds' && Schema::hasColumn($table, 'recruitment_contract_id')) {
                $query = DB::table($table)->where('recruitment_contract_id', $contractId);
                if ($lock) $query->lockForUpdate();
                if ($query->first(['id'])) return 'لا يمكن تغيير العميل لوجود ' . $label . ' مرتبط بالعقد.';
            }
            // Legacy receipts/journals can have no contract FK; match their exact contract number.
            $query = DB::table($table)->where('statement', 'like', '%' . $contractId . '%');
            if ($lock) $query->lockForUpdate();
            foreach ($query->get(['statement']) as $row) {
                if (preg_match('/(?:للعقد|العقد|contract)\s*#\s*' . $contractId . '(?!\d)/iu', (string) $row->statement)) {
                    return 'لا يمكن تغيير العميل لوجود ' . $label . ' مرتبط بالعقد.';
                }
            }
        }
        if (Schema::hasTable('sponsorship_transfers')) {
            $query = DB::table('sponsorship_transfers')->where('contract_id', $contractId);
            if ($lock) $query->lockForUpdate();
            if ($query->first(['id'])) {
                return 'العقد مرتبط بنقل خدمات؛ يجب معالجة صاحب العقد من مسار نقل الخدمات.';
            }
        }
        return null;
    }

    public function change(Contract $contract, int $clientId, int $expectedClientId, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $clientId, $expectedClientId, $reason) {
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            app(BranchAccessService::class)->assertCanAccessModel($locked, null, 'contracts');
            if ((int) $locked->user_id !== $expectedClientId) {
                throw ValidationException::withMessages(['current_client_id' => 'تغيّر العميل المرتبط بالعقد. حدّث الصفحة ثم أعد المحاولة.']);
            }
            if ((int) $locked->user_id === $clientId) {
                throw ValidationException::withMessages(['client_id' => 'اختر عميلًا مختلفًا عن العميل الحالي.']);
            }
            if ($blocker = $this->blockingReason($locked, true)) {
                throw ValidationException::withMessages(['client_id' => $blocker]);
            }
            $client = $this->eligibleClientsQuery()->whereKey($clientId)->lockForUpdate()->first();
            if (!$client) {
                throw ValidationException::withMessages(['client_id' => 'العميل المختار غير متاح أو لا يمكنك الوصول إليه.']);
            }
            $order = $locked->order_id
                ? \App\Models\ingaz\Order::query()->lockForUpdate()->find($locked->order_id)
                : null;
            if ($locked->order_id && (!$order || (int) $order->user_id !== (int) $locked->user_id)) {
                throw ValidationException::withMessages(['client_id' => 'بيانات صاحب الطلب لا تطابق العقد؛ يلزم تصحيح الارتباط قبل تغيير العميل.']);
            }
            if ($order) {
                $shared = DB::table('contracts')->where('order_id', $order->id)->where('id', '!=', $locked->id)->lockForUpdate()->first(['id']);
                $rental = Schema::hasTable('rental_contracts')
                    ? DB::table('rental_contracts')->where('order_id', $order->id)->lockForUpdate()->first(['id'])
                    : null;
                if ($shared || $rental) {
                    throw ValidationException::withMessages(['client_id' => 'الطلب مرتبط بعقد آخر؛ لا يمكن تغيير صاحبه من هذا الإجراء.']);
                }
            }
            $oldId = $locked->user_id;
            $oldName = $locked->user?->name;
            $locked->update(['user_id' => $client->id]);
            if ($order) $order->update(['user_id' => $client->id]);
            activity('contract_client_change')->performedOn($locked)->causedBy(auth('admin')->user())
                ->event('client_changed')->withProperties([
                    'old_user_id' => $oldId,
                    'old_client_name' => $oldName,
                    'new_user_id' => $client->id,
                    'new_client_name' => $client->name,
                    'order_id' => $order?->id,
                    'reason' => $reason,
                ])->log('تم تغيير عميل العقد من ' . ($oldName ?: 'غير محدد') . ' إلى ' . $client->name . '؛ السبب: ' . $reason);
            return $locked->refresh();
        });
    }
}
