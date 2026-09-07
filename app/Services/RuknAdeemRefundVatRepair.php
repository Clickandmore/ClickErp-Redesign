<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/** One-off repair. Intentionally independent of tax settings and migrations. */
class RuknAdeemRefundVatRepair
{
    public const DATABASE = 'ruknadem_dashboard';
    public const MARKER = 'rukn_adeem_refund_vat_20260907';
    public const CASES = [
        [16272, 3685, 3672, '2026-08-17', 5490, 684, 3678],
        [16723, 3779, 3764, '2026-08-19', 4500, null, 799],
        [16733, 3781, 3766, '2026-08-19', 12000, null, 395],
        [17522, 3967, 3946, '2026-08-26', 21000, 351, 1839],
    ];

    protected function actualDatabase(): string
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('This repair requires the Rukn Adeem MySQL database.');
        }
        return (string) $connection->selectOne('SELECT DATABASE() AS db', [], false)->db;
    }

    public function run(bool $apply = false, ?int $adminId = null): array
    {
        if ($this->actualDatabase() !== self::DATABASE) {
            throw new RuntimeException('Refused: database must be exactly '.self::DATABASE);
        }
        return DB::transaction(function () use ($apply, $adminId) {
            if ($apply && (!$adminId || !DB::table('admins')->where('id', $adminId)->exists())) {
                throw new RuntimeException('A valid --admin-id is required for the audit log.');
            }
            foreach ([[5462, 15600, 'debtor'], [5463, 22200, 'creditor']] as [$id, $code, $status]) {
                $account = DB::table('fn_accounts')->where('id', $id)->lockForUpdate()->first();
                if (!$account || (int) $account->code !== $code || $account->status !== $status
                    || DB::table('fn_accounts')->where('parent_id', $id)->exists()) {
                    throw new RuntimeException("Account fingerprint mismatch: {$id}");
                }
            }
            $pending = [];
            $done = [];
            foreach (self::CASES as [$detailId, $journalId, $code, $date, $cents, $center, $originalId]) {
                $journal = DB::table('fn_constraints')->where('id', $journalId)->lockForUpdate()->first();
                if (!$journal || $journal->deleted_at !== null || $journal->approval_status !== 'approved'
                    || (int) $journal->code !== $code || substr($journal->date, 0, 10) !== $date) {
                    throw new RuntimeException("Journal fingerprint mismatch: {$journalId}");
                }
                $lines = DB::table('fn_constraint_details')->where('constraint_id', $journalId)
                    ->whereNull('deleted_at')->orderBy('id')->lockForUpdate()->get();
                $row = $lines->firstWhere('id', $detailId);
                $balance = $lines->sum(fn ($line) => (float) $line->debtor_value - (float) $line->creditor_value);
                if (abs($balance) > 0.005 || !$row || $row->type !== 'debtor'
                    || substr($row->date, 0, 10) !== $date
                    || abs((float) $row->debtor_value - $cents / 100) > 0.00001
                    || abs((float) $row->creditor_value) > 0.00001
                    || ($row->cost_center_id === null ? null : (int) $row->cost_center_id) !== $center) {
                    throw new RuntimeException("Detail fingerprint/balance mismatch: {$detailId}");
                }
                $original = DB::table('fn_constraints')->where('id', $originalId)->whereNull('deleted_at')->first();
                $tax = DB::table('fn_constraint_details')->where('constraint_id', $originalId)
                    ->where('account_id', 5463)->whereNull('deleted_at')->get();
                if (!$original || $original->approval_status !== 'approved' || $tax->count() !== 1
                    || abs((float) $tax[0]->creditor_value - $cents / 100) > 0.00001
                    || abs((float) $tax[0]->debtor_value) > 0.00001) {
                    throw new RuntimeException("Original invoice VAT mismatch: {$originalId}");
                }
                $logs = DB::table('fn_constraint_logs')->where('constraint_id', $journalId)
                    ->where('action', self::MARKER)->get();
                if ((int) $row->account_id === 5463 && $logs->count() === 1) {
                    $metadata = json_decode($logs[0]->metadata, true);
                    if (($metadata['before']['id'] ?? null) != $detailId
                        || ($metadata['before']['account_id'] ?? null) != 5462
                        || ($metadata['after'] ?? null) != (array) $row) {
                        throw new RuntimeException("Audit mismatch: {$detailId}");
                    }
                    $done[] = $detailId;
                } elseif ((int) $row->account_id === 5462 && $logs->isEmpty()) {
                    $pending[] = (array) $row;
                } else {
                    throw new RuntimeException("Unexpected account or repair log: {$detailId}");
                }
            }
            if ($pending && $done) {
                throw new RuntimeException('Partial repair detected; manual review required. Nothing changed.');
            }
            if ($apply) {
                foreach ($pending as $before) {
                    // Only account_id changes: preserve amounts, dates, timestamps and all other fields.
                    $changed = DB::table('fn_constraint_details')->where('id', $before['id'])
                        ->where('account_id', 5462)->update(['account_id' => 5463]);
                    if ($changed !== 1) {
                        throw new RuntimeException('Concurrent detail change; repair rolled back.');
                    }
                    $after = (array) DB::table('fn_constraint_details')->where('id', $before['id'])->first();
                    $expected = array_replace($before, ['account_id' => 5463]);
                    if ($after != $expected) {
                        throw new RuntimeException('Unexpected change; repair rolled back.');
                    }
                    DB::table('fn_constraint_logs')->insert([
                        'constraint_id' => $before['constraint_id'], 'admin_id' => $adminId,
                        'action' => self::MARKER,
                        'description' => 'تصحيح حساب ضريبة مرتجع المبيعات لركن أديم من 5462 إلى 5463',
                        'metadata' => json_encode(['database' => self::DATABASE, 'before' => $before, 'after' => $after], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
            return ['status' => $done ? 'already_applied' : ($apply ? 'applied' : 'preview'),
                'detail_ids' => array_column(self::CASES, 0), 'total' => '429.90',
                'changed' => $apply ? count($pending) : 0];
        });
    }
}
