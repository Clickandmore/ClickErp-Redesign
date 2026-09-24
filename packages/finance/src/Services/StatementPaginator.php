<?php

namespace Nami\Finance\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class StatementPaginator extends LengthAwarePaginator
{
    public object $totals;
    public object $previous;

    public static function fromQuery(Builder $query, bool $useAccountStatus = true, int $perPage = 50): self
    {
        $totals = self::summarize((clone $query)->reorder(), $useAccountStatus);
        $page = min(max(1, self::resolveCurrentPage()), max(1, (int) ceil($totals->row_count / $perPage)));
        $offset = ($page - 1) * $perPage;
        $previous = $offset > 0
            ? self::summarize((clone $query)->limit($offset), $useAccountStatus)
            : (object) ['debtor' => 0, 'creditor' => 0, 'balance' => 0, 'row_count' => 0];

        $paginator = new self((clone $query)->forPage($page, $perPage)->get(), (int) $totals->row_count, $perPage, $page, [
            'path' => self::resolveCurrentPath(),
        ]);
        $paginator->totals = $totals;
        $paginator->previous = $previous;

        return $paginator->withQueryString();
    }

    private static function summarize(Builder $query, bool $useAccountStatus): object
    {
        $difference = 'ROUND(debtor_value, 2) - ROUND(creditor_value, 2)';
        $balance = $useAccountStatus
            ? "CASE WHEN status = 'debtor' THEN ($difference) ELSE -($difference) END"
            : $difference;

        return $query->newQuery()->fromSub($query, 'statement_rows')
            ->selectRaw('COUNT(*) as row_count, COALESCE(SUM(ROUND(debtor_value, 2)), 0) as debtor, COALESCE(SUM(ROUND(creditor_value, 2)), 0) as creditor')
            ->selectRaw("COALESCE(SUM($balance), 0) as balance")
            ->first();
    }
}
