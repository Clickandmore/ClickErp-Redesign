<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * تقرير المستحق على عملاء التأجير — عقد بعقد، من كشف الحركة الفعلية
 * (RentalReceivablesLedger) لا من أرقام الفاتورة المحفوظة.
 */
class ClientReceivablesService
{
    public const STATES = ['all' => 'كل العقود', 'ended' => 'منتهية المدة', 'open' => 'سارية المدة', 'closed' => 'مغلقة', 'cancelled' => 'ملغاة'];

    public const BALANCES = ['due' => 'عليها مستحقات', 'credit' => 'رصيد دائن للعميل', 'all' => 'كل العقود'];

    public const SORTS = ['latest' => 'الأحدث أولاً', 'amount' => 'الأعلى مستحقاً أولاً'];

    public function __construct(private RentalReceivablesLedger $ledger)
    {
    }

    /** العقود المطابقة للفلاتر: الأحدث أولاً (افتراضياً) أو الأعلى متبقياً. */
    public function rows(array $filters, ?string $to = null): Collection
    {
        $to = $to ?: now()->toDateString();
        $today = now()->toDateString();
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $state = $filters['state'] ?? 'all';
        $balance = $filters['balance'] ?? 'due';
        $extended = (string) ($filters['extended'] ?? '');

        return $this->visible($to)
            ->filter(fn ($c) => empty($filters['client_id']) || (int) $c->user_id === (int) $filters['client_id'])
            ->filter(function ($c) use ($search) {
                if ($search === '') {
                    return true;
                }
                foreach ([$c->client_name, $c->client_phone, $c->contract_number] as $value) {
                    if ($value !== null && str_contains(mb_strtolower((string) $value), $search)) {
                        return true;
                    }
                }

                return false;
            })
            ->filter(fn ($c) => match ($balance) {
                'due' => $c->remaining > 0.005,
                'credit' => $c->remaining < -0.005,
                default => true,
            })
            ->filter(function ($c) use ($state, $today) {
                $closed = $c->closed_at || $c->status === 'completed';
                return match ($state) {
                    'closed' => $closed,
                    'cancelled' => $c->status === 'cancelled',
                    'ended' => !$closed && $c->status !== 'cancelled' && $c->effective_end && substr($c->effective_end, 0, 10) < $today,
                    'open' => !$closed && $c->status !== 'cancelled' && $c->effective_end && substr($c->effective_end, 0, 10) >= $today,
                    default => true,
                };
            })
            ->filter(fn ($c) => $extended === '' || ($extended === '1' ? $c->extension_count > 0 : $c->extension_count === 0))
            ->sort(($filters['sort'] ?? 'latest') === 'amount'
                ? fn ($a, $b) => [$b->remaining, $a->id] <=> [$a->remaining, $b->id]
                : fn ($a, $b) => [self::contractDate($b), $b->id] <=> [self::contractDate($a), $a->id])
            ->values();
    }

    /** تاريخ العقد للترتيب: بداية الإيجار، وإلا تاريخ إنشائه. */
    public static function contractDate(object $contract): string
    {
        return substr((string) ($contract->start_date ?: $contract->created_at), 0, 10);
    }

    /**
     * العقود مجمّعة لكل عميل، حتى تظهر عقوده السابقة غير المصفّاة تحت اسمه مع
     * إجمالي ما عليه. ترتيب العملاء يتبع ترتيب أول عقد لهم في النتائج.
     */
    public function clients(Collection $rows, ?string $to = null): Collection
    {
        // أحدث عقد للعميل من كل عقوده (لا من المعروض فقط)، لتمييز العقود السابقة.
        $all = $this->visible($to ?: now()->toDateString())->filter(fn ($c) => $c->user_id)->groupBy('user_id');

        return $rows->groupBy(fn ($c) => $c->user_id ? 'u' . $c->user_id : 'c' . $c->id)
            ->map(function (Collection $contracts) use ($all) {
                $first = $contracts->first();

                return (object) [
                    'user_id' => $first->user_id,
                    'client_name' => $first->client_name,
                    'client_phone' => $first->client_phone,
                    'account_id' => $first->account_id,
                    'client_ledger_balance' => $first->client_ledger_balance,
                    'client_pending' => $first->client_pending,
                    'client_mixed' => $first->client_mixed,
                    'contracts' => $contracts->values(),
                    'contracts_count' => $contracts->count(),
                    'all_contracts_count' => $first->user_id ? $all->get($first->user_id, collect())->count() : 1,
                    'newest_contract_date' => $first->user_id
                        ? $all->get($first->user_id, collect())->map(fn ($c) => self::contractDate($c))->max()
                        : self::contractDate($first),
                    'due_contracts' => $contracts->filter(fn ($c) => $c->remaining > 0.005)->count(),
                    'latest_date' => $contracts->map(fn ($c) => self::contractDate($c))->max(),
                    'due' => round($contracts->sum(fn ($c) => max($c->remaining, 0)), 2),
                    'credit' => round($contracts->sum(fn ($c) => max(-$c->remaining, 0)), 2),
                    'net' => round($contracts->sum('remaining'), 2),
                ];
            })
            ->values();
    }

    /**
     * مجاميع النتائج، ومطابقتها مع حسابات نفس العملاء في دفتر الأستاذ.
     */
    public function totals(Collection $rows, ?string $to = null): object
    {
        $clients = $rows->pluck('user_id')->filter()->unique();
        // المطابقة على كل عقود العميل لا على المعروض منها فقط، لأن رصيد حسابه واحد.
        $clientContracts = $this->visible($to ?: now()->toDateString())->whereIn('user_id', $clients->all());
        $perClient = $clientContracts->groupBy('user_id')->map->first();

        return (object) [
            'contracts' => $rows->count(),
            'clients' => $clients->count(),
            'billed' => round($rows->sum('billed'), 2),
            'paid' => round($rows->sum('paid'), 2),
            'adjustments' => round($rows->sum('adjustments'), 2),
            'offsets' => round($rows->sum('offsets'), 2),
            'due' => round($rows->sum(fn ($c) => max($c->remaining, 0)), 2),
            'credit' => round($rows->sum(fn ($c) => max(-$c->remaining, 0)), 2),
            'net' => round($rows->sum('remaining'), 2),
            'reconciliation' => (object) [
                'rental_net' => round($clientContracts->sum('remaining'), 2),
                'ledger' => round($perClient->sum(fn ($c) => (float) $c->client_ledger_balance), 2),
                'pending' => round($perClient->sum('client_pending'), 2),
                'other_business' => round($perClient->sum('client_other_business'), 2),
                'unposted' => round($clientContracts->sum('unposted'), 2),
                'unbacked' => round($clientContracts->sum('unbacked'), 2),
                'mixed_cap' => round($clientContracts->sum('client_cap'), 2),
                'clients_without_account' => $perClient->filter(fn ($c) => !$c->account_id)->count(),
                'mixed_clients' => $perClient->where('client_mixed', true)->count(),
            ],
        ];
    }

    /** كشف حركة كل عقد مطلوب (للعقود المرئية فقط). */
    public function movements(array $ids, ?string $to = null): Collection
    {
        $ids = array_flip(array_map('intval', $ids));

        return $this->visible($to ?: now()->toDateString())
            ->filter(fn ($c) => isset($ids[$c->id]))
            ->mapWithKeys(fn ($c) => [$c->id => collect($c->movements)]);
    }

    /** المطالبات المفتوحة لكل عقد مطلوب، بعد توزيع الدفعات والتسويات. */
    public function documents(array $ids, ?string $to = null): Collection
    {
        $ids = array_flip(array_map('intval', $ids));

        return $this->visible($to ?: now()->toDateString())
            ->filter(fn ($c) => isset($ids[$c->id]))
            ->mapWithKeys(fn ($c) => [$c->id => collect($c->items)]);
    }

    public function extensions(array $ids)
    {
        return \App\Support\BranchReportFilter::apply(DB::table('rental_contract_extensions'), 'rental_contract_extensions')
            ->whereIn('rental_contract_id', $ids)->orderBy('start_date')->get()->groupBy('rental_contract_id');
    }

    private function visible(string $to): Collection
    {
        $visible = array_flip($this->ledger->visibleIds());

        return $this->ledger->contracts($to)->filter(fn ($c) => isset($visible[$c->id]));
    }
}
