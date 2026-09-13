<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\DeportationProcedure;
use App\Models\DeportationProcedureLog;
use App\Models\ingaz\Maid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إنهاء الترحيل وإلغاؤه من مكان واحد (كان المنطق مكرراً في شاشة الإيواء وشاشة رحلات الترحيل).
 *
 * الإنهاء:
 *  - القيد 18 عبر دفتر الإيواء: مدين المكتب الخارجي (أو خسائر الإيواء) / دائن أصل الإيواء برصيد العاملة.
 *  - العاملة: status = deported، غير متاحة، خارج الإيواء.
 *  - العقد: مرتجع (back) مع سجل حالة يحمل الحالة السابقة، وتُمسح حقول الإيواء.
 * كل ذلك في معاملة واحدة؛ فشل القيد يُلغي الإنهاء.
 */
class DeportationCompletionService
{
    public function __construct(protected ShelterCostLedgerService $shelterLedger)
    {
    }

    /**
     * @return array{constraint: ?\Nami\Finance\Models\Constraint, amount: float, debit: string, already_completed: bool}
     */
    public function complete(DeportationProcedure $procedure, ?string $date = null, bool $supportsQuickTags = false): array
    {
        return DB::transaction(function () use ($procedure, $date, $supportsQuickTags) {
            $procedure = DeportationProcedure::query()->lockForUpdate()->with(['contract.info', 'contract.maid'])->findOrFail($procedure->id);
            $contract = $procedure->contract;
            $alreadyCompleted = $procedure->status === 'completed';
            $date = $date ?: now()->toDateString();

            if (!$alreadyCompleted) {
                $procedure->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => auth('admin')->id(),
                ]);
            }

            // القيد 18 (محمي من التكرار داخل الدفتر بمصدر الإجراء).
            $release = ['constraint' => null, 'amount' => 0.0, 'debit' => 'none'];
            if ($contract && Schema::hasTable('shelter_cost_entries')) {
                $release = $this->shelterLedger->releaseToDeportation($procedure, $date);
                if (($release['constraint'] ?? null) && Schema::hasColumn('deportation_procedures', 'constraint_id') && !$procedure->constraint_id) {
                    $procedure->forceFill(['constraint_id' => $release['constraint']->id])->saveQuietly();
                }
            }

            if ($contract && !$alreadyCompleted) {
                $this->closeContract($contract, $supportsQuickTags);
                $this->markMaidDeported($contract->maid);

                DeportationProcedureLog::logCompletion(
                    $procedure->id,
                    auth('admin')->id(),
                    'تم إكمال إجراءات الترحيل'
                    . (($release['amount'] ?? 0) > 0
                        ? ' — قيد الترحيل بمبلغ ' . number_format((float) $release['amount'], 2) . ' (المدين: ' . $release['debit'] . ')'
                        : ' — لا رصيد إيواء للعاملة، بلا قيد')
                );
            }

            return [
                'constraint' => $release['constraint'] ?? null,
                'amount' => (float) ($release['amount'] ?? 0),
                'debit' => (string) ($release['debit'] ?? 'none'),
                'already_completed' => $alreadyCompleted,
            ];
        });
    }

    /**
     * إلغاء إجراء ترحيل بدأ خطأً قبل اكتمال رحلة السفر. لا قيد؛ العقد يبقى في الإيواء.
     */
    public function cancel(DeportationProcedure $procedure, string $reason): DeportationProcedure
    {
        return DB::transaction(function () use ($procedure, $reason) {
            $procedure = DeportationProcedure::query()->lockForUpdate()->with('trip')->findOrFail($procedure->id);

            if ($procedure->status === 'completed') {
                throw new \DomainException('لا يمكن إلغاء إجراء ترحيل مكتمل.');
            }
            if ($procedure->trip && $procedure->trip->status === 'completed') {
                throw new \DomainException('لا يمكن إلغاء الإجراء لأن رحلة الترحيل اكتملت بالفعل.');
            }

            $payload = ['status' => 'cancelled'];
            if (Schema::hasColumn('deportation_procedures', 'cancel_reason')) {
                $payload += [
                    'cancel_reason' => $reason,
                    'cancelled_at' => now(),
                    'cancelled_by' => auth('admin')->id(),
                ];
            }
            $procedure->forceFill($payload)->save();

            if ($procedure->trip && !in_array($procedure->trip->status, ['completed', 'cancelled'], true)) {
                $procedure->trip->update(['status' => 'cancelled']);
            }

            DeportationProcedureLog::query()->create([
                'deportation_procedure_id' => $procedure->id,
                'admin_id' => auth('admin')->id(),
                'action_type' => 'delete',
                'description' => 'تم إلغاء إجراء الترحيل: ' . $reason,
            ]);

            return $procedure;
        });
    }

    private function closeContract(Contract $contract, bool $supportsQuickTags): void
    {
        $previousStatus = $contract->status;
        $contract->update(['status' => ContractStatusEnum::Back->value]);

        $log = [
            'status' => ContractStatusEnum::Back->value,
            'date' => now()->toDateString(),
            'notes' => 'تم ترحيل العاملة وإعادتها للمكتب الخارجي',
        ];
        if (Schema::hasColumn('contract_status_logs', 'previous_status')) {
            $log['previous_status'] = $previousStatus;
        }
        if (method_exists($contract, 'contract_status_logs')) {
            $contract->contract_status_logs()->create($log);
        }

        if ($contract->info) {
            $infoResetPayload = [
                'shelter_id' => null,
                'wants_to_work' => null,
                'maid_date_back' => now(),
                'receipt_source' => null,
            ];
            if ($supportsQuickTags && Schema::hasColumn('contract_infos', 'shelter_quick_tags')) {
                $infoResetPayload['shelter_quick_tags'] = null;
            }
            $contract->info->update($infoResetPayload);
        }
    }

    private function markMaidDeported(?Maid $maid): void
    {
        if (!$maid) {
            return;
        }

        $payload = ['status' => 'deported', 'available' => false];
        if (Schema::hasColumn('maids', 'maid_accommodation_type')) {
            $payload['maid_accommodation_type'] = null;
        }
        $maid->forceFill($payload)->save();
    }
}
