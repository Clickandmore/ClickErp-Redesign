<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\OutboundMessageLog;
use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class ContractMessageHistoryService
{
    public const LEGACY_SOURCES = [
        'recruitment' => ['recruitment_stage', 'recruitment:send-stage-notifications'],
        'rental' => ['rental_contract_expiry_reminder', 'rental_contract_overdue_sms', 'rental_payment_link',
            'rental-contracts:send-expiry-reminders', 'legacy:rental_contract_expiry_reminders'],
    ];

    public static function typeForSource(string $source): ?string
    {
        foreach (self::LEGACY_SOURCES as $type => $sources) {
            if (in_array($source, $sources, true)) return $type;
        }
        return null;
    }

    public static function context(Model $contract): array
    {
        $type = match (true) {
            $contract instanceof RentalContract => 'rental',
            $contract instanceof Contract => 'recruitment',
            default => null,
        };
        return $type ? ['contract_type' => $type, 'contract_id' => $contract->getKey(),
            'user_id' => $contract->user_id, 'branch_id' => $contract->branch_id] : [];
    }

    /** The caller must authorize access to the contract before reading its messages. */
    public function forContract(Model $contract): LengthAwarePaginator
    {
        $context = self::context($contract);
        $pageName = 'contract_messages_page';
        if (!$context || !Schema::hasTable('outbound_message_logs')) {
            return new LengthAwarePaginator([], 0, 15, 1, ['pageName' => $pageName]);
        }
        return OutboundMessageLog::query()->with('actor:id,name')
            ->where('purpose', '!=', 'verification')
            ->whereIn('context->contract_id', [$contract->getKey(), (string) $contract->getKey()])
            ->where(function ($query) use ($context) {
                $query->where('context->contract_type', $context['contract_type'])
                    ->orWhere(function ($legacy) use ($context) {
                        $legacy->whereNull('context->contract_type')->whereIn('source', self::LEGACY_SOURCES[$context['contract_type']]);
                    });
            })
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(15, ['*'], $pageName)->withQueryString()->fragment('contract-messages');
    }
}
