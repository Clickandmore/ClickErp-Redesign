<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ClientReceivablesService
{
    public const STATES = ['all' => 'كل العقود', 'ended' => 'منتهية المدة', 'open' => 'سارية المدة', 'closed' => 'مغلقة', 'cancelled' => 'ملغاة'];

    public function query(array $filters): Builder
    {
        $documents = app(OperationalSummaryService::class)->rentalReceivableBillingQuery(now()->toDateString());
        $balances = DB::query()->fromSub($documents, 'documents')->select('rental_contract_id')
            ->selectRaw('SUM(total_due) as billed, SUM(paid_amount) as paid, SUM(adjustment_amount) as adjustments, SUM(remaining_amount) as remaining')
            ->groupBy('rental_contract_id');
        $extensions = BranchReportFilter::apply(DB::table('rental_contract_extensions'), 'rental_contract_extensions')
            ->select('rental_contract_id')->selectRaw('COUNT(*) as extension_count, MAX(end_date) as extension_end')
            ->groupBy('rental_contract_id');
        $end = 'CASE WHEN e.extension_end IS NULL THEN i.ingaz_to WHEN i.ingaz_to IS NULL THEN e.extension_end ELSE GREATEST(i.ingaz_to, e.extension_end) END';
        $query = BranchReportFilter::apply(DB::table('rental_contracts as c'), 'c')
            ->leftJoinSub($balances, 'b', 'b.rental_contract_id', '=', 'c.id')
            ->leftJoinSub($extensions, 'e', 'e.rental_contract_id', '=', 'c.id')
            ->leftJoin('rental_contract_infos as i', 'i.rental_contract_id', '=', 'c.id')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->select(['c.id', 'c.user_id', 'c.contract_number', 'c.status', 'c.closed_at', 'u.name as client_name', 'u.phone', 'i.ingaz_from as start_date', 'i.ingaz_to as original_end'])
            ->selectRaw("{$end} as effective_end, COALESCE(e.extension_count, 0) as extension_count, COALESCE(b.billed, 0) as billed, COALESCE(b.paid, 0) as paid, COALESCE(b.adjustments, 0) as adjustments, COALESCE(b.remaining, 0) as remaining");

        if (!empty($filters['client_id'])) {
            $query->where('c.user_id', $filters['client_id']);
        }
        if ($search = $filters['search'] ?? '') {
            $query->where(function ($q) use ($search) {
                $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
                foreach (['u.name', 'u.phone', 'c.contract_number'] as $column) {
                    $q->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }
        if (($filters['balance'] ?? 'due') === 'due') {
            $query->where('b.remaining', '>', 0);
        }
        $state = $filters['state'] ?? 'all';
        if ($state === 'closed') {
            $query->where(function ($q) { $q->whereNotNull('c.closed_at')->orWhere('c.status', 'completed'); });
        } elseif ($state === 'cancelled') {
            $query->where('c.status', 'cancelled');
        } elseif (in_array($state, ['ended', 'open'], true)) {
            $query->whereNull('c.closed_at')->whereNotIn('c.status', ['completed', 'cancelled'])
                ->whereRaw("DATE({$end}) " . ($state === 'ended' ? '<' : '>=') . ' ?', [now()->toDateString()]);
        }
        if (($filters['extended'] ?? '') !== '') {
            ($filters['extended'] === '1') ? $query->where('e.extension_count', '>', 0) : $query->whereNull('e.extension_count');
        }
        return $query;
    }

    public function documents(array $ids)
    {
        return app(OperationalSummaryService::class)->rentalReceivableBillingQuery(now()->toDateString())
            ->whereIn('rental_contract_id', $ids)->orderBy('created_at')->get()->groupBy('rental_contract_id');
    }

    public function extensions(array $ids)
    {
        return BranchReportFilter::apply(DB::table('rental_contract_extensions'), 'rental_contract_extensions')
            ->whereIn('rental_contract_id', $ids)->orderBy('start_date')->get()->groupBy('rental_contract_id');
    }
}
