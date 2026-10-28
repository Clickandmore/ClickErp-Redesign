<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Shared filters for the contract table, its statistics and both exports. */
class RecruitmentContractReporting
{
    private static function validDate(string $column): string
    {
        return "CASE WHEN YEAR($column) >= 1900 THEN DATE($column) ELSE NULL END";
    }

    public static function hasArrivalSql(): string
    {
        return "(contracts.status IN ('arrival','completed','under_warranty','closed','refunded')
            OR EXISTS (SELECT 1 FROM travel_histories t WHERE t.order_id=contracts.id AND t.deleted_at IS NULL AND t.status='completed')
            OR EXISTS (SELECT 1 FROM contract_status_logs l WHERE l.contract_id=contracts.id AND l.status IN ('arrival','completed','under_warranty','closed'))
            OR " . self::validDate('contracts.warranty_start_date') . ' IS NOT NULL)';
    }

    public static function arrivalDateSql(): string
    {
        $warranty = self::validDate('contracts.warranty_start_date');
        $tripDate = self::validDate('t.arrival_date');
        $tripAt = self::validDate('t.arrived_at');
        $info = self::validDate('i.arrival_date');
        $maid = self::validDate('m.arrival_date');
        $log = self::validDate('l.date');
        $proof = self::hasArrivalSql();
        return "COALESCE($warranty,
            (SELECT COALESCE($tripDate,$tripAt) FROM travel_histories t WHERE t.order_id=contracts.id AND t.deleted_at IS NULL AND t.status='completed' ORDER BY t.id DESC LIMIT 1),
            CASE WHEN $proof THEN (SELECT $info FROM contract_infos i WHERE i.contract_id=contracts.id LIMIT 1) END,
            CASE WHEN $proof THEN (SELECT $maid FROM maids m WHERE m.id=contracts.maid_id LIMIT 1) END,
            (SELECT $log FROM contract_status_logs l WHERE l.contract_id=contracts.id AND l.status='arrival' AND $log IS NOT NULL ORDER BY l.id LIMIT 1))";
    }

    public static function expectedDateSql(): string
    {
        $date = self::validDate('t.arrival_date');
        return "(SELECT CASE WHEN t.status NOT IN ('completed','cancelled','canceled') THEN $date END FROM travel_histories t
            WHERE t.order_id=contracts.id AND t.deleted_at IS NULL ORDER BY t.id DESC LIMIT 1)";
    }

    public static function dateSql(string $type): string
    {
        return match ($type) {
            'arrival' => self::arrivalDateSql(),
            'created' => 'DATE(contracts.created_at)',
            'delivery' => Schema::hasColumn('contracts','client_delivered_at') ? 'DATE(contracts.client_delivered_at)' : 'NULL',
            default => 'COALESCE((SELECT '.self::validDate('i.contract_date').' FROM contract_infos i WHERE i.contract_id=contracts.id LIMIT 1),'.self::validDate('contracts.contract_date').')',
        };
    }

    public const SCOPES = ['arrived_month', 'expected_month', 'delayed', 'waiting', 'unscheduled', 'undelivered', 'arrived', 'missing'];

    public static function applyScope($query, ?string $scope): void
    {
        $arrival = self::arrivalDateSql();
        $proof = self::hasArrivalSql();
        $waiting = "NOT $proof AND COALESCE(contracts.status,'') NOT IN ('back','refunded','cancelled','canceled','closed','sari','shelter','completed')";
        switch ($scope) {
            case 'arrived': $query->whereRaw("$arrival IS NOT NULL"); break;
            case 'arrived_month':
                $query->whereRaw("$arrival BETWEEN ? AND ?", [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
                break;
            case 'waiting': $query->whereRaw($waiting); break;
            case 'expected_month':
                $query->whereRaw($waiting)->whereRaw(self::expectedDateSql().' BETWEEN ? AND ?', [today()->toDateString(), now()->endOfMonth()->toDateString()]);
                break;
            case 'unscheduled': $query->whereRaw($waiting)->whereRaw(self::expectedDateSql().' IS NULL'); break;
            case 'delayed': $query->whereRaw($waiting)->whereRaw(self::expectedDateSql().' < ?', [today()->toDateString()]); break;
            case 'missing': $query->whereRaw($proof)->whereRaw("$arrival IS NULL"); break;
            case 'undelivered':
                $query->whereRaw("$arrival <= ?", [today()->toDateString()])
                    ->whereNotIn('contracts.status',['back','refunded','cancelled','canceled']);
                if (Schema::hasColumn('contracts','client_delivered_at')) $query->whereNull('contracts.client_delivered_at');
                break;
        }
    }

    public static function apply($query, Request $request): void
    {
        $request->validate([
            'date_type'=>'nullable|in:contract,created,arrival,delivery',
            'arrival_scope'=>'nullable|in:'.implode(',', self::SCOPES),
            'date_from'=>'nullable|date_format:Y-m-d|after_or_equal:1900-01-01',
            'date_to'=>'nullable|date_format:Y-m-d|after_or_equal:1900-01-01',
        ]);
        if ($request->filled('date_from') && $request->filled('date_to') && $request->date_from > $request->date_to) {
            throw \Illuminate\Validation\ValidationException::withMessages(['date_to'=>'نهاية الفترة يجب أن تساوي بدايتها أو تأتي بعدها.']);
        }
        $sql = self::dateSql($request->input('date_type') ?: 'contract');
        if ($request->filled('date_from')) $query->whereRaw("$sql >= ?", [$request->date_from]);
        if ($request->filled('date_to')) $query->whereRaw("$sql <= ?", [$request->date_to]);
        self::applyScope($query,$request->input('arrival_scope'));
    }
}