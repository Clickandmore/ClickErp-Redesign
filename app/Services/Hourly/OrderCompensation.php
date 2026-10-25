<?php
namespace App\Services\Hourly;

use App\Models\auth\Admin;
use App\Models\Hourly\HourlyRentalOrder;
use App\Models\ingaz\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Compensating a customer after a problem is a separate expense linked to the order and, optionally, one visit.
 * The issued invoice and the order total never change; a service that was not delivered goes through cancellation and refund instead.
 */
class OrderCompensation
{
    public const REASONS = [
        'delay' => 'تأخير عن الموعد', 'worker_absence' => 'تعذّر حضور العاملة', 'service_quality' => 'مستوى الخدمة',
        'staff_conduct' => 'سلوك الطاقم', 'damage' => 'ضرر أو تلف', 'other' => 'سبب آخر',
    ];
    public const DESTINATIONS = ['wallet' => 'محفظة العميل', 'cash' => 'نقدًا', 'bank_transfer' => 'تحويل بنكي'];
    public const STATUSES = ['credited' => 'أُضيف للمحفظة', 'pending_payout' => 'بانتظار الصرف', 'paid_out' => 'صُرف للعميل'];

    public static function ready(): bool
    {
        return Schema::hasTable('hourly_order_compensations');
    }

    /** @return Collection<int, object> the order's compensations, newest first */
    public function forOrder(HourlyRentalOrder $order): Collection
    {
        if (!self::ready()) return collect();
        $staff = Schema::hasTable('admins');
        return DB::table('hourly_order_compensations as c')
            ->when($staff, fn ($q) => $q->leftJoin('admins as a', 'a.id', '=', 'c.created_by'))
            ->where('c.hourly_rental_order_id', $order->id)->orderByDesc('c.id')
            ->get($staff ? ['c.*', 'a.name as created_by_name'] : ['c.*']);
    }

    /** What the customer paid for the order or the visit, minus what was already compensated against it. */
    public function limit(HourlyRentalOrder $order, ?int $visitId = null): float
    {
        $paid = $visitId
            ? (float) (app(VisitPaymentAllocation::class)->forOrder($order)[$visitId]['paid'] ?? 0)
            : (float) $order->paid_amount;
        $given = self::ready() ? (float) DB::table('hourly_order_compensations')->where('hourly_rental_order_id', $order->id)
            ->when($visitId, fn ($q) => $q->where('visit_id', $visitId))->sum('amount') : 0;
        return max(0, round($paid - $given, 2));
    }

    /**
     * @param array{amount: float|string, reason: string, note?: ?string, destination: string, visit_id?: ?int, request_token: string} $data
     */
    public function create(HourlyRentalOrder $candidate, array $data, Admin $admin, bool $mayExceedPaid = false): object
    {
        abort_unless(self::ready(), 503, 'يجب تشغيل ترحيل التعويضات أولًا.');
        $row = DB::transaction(function () use ($candidate, $data, $admin, $mayExceedPaid) {
            User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            $order = HourlyRentalOrder::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $old = DB::table('hourly_order_compensations')->where('request_token', $data['request_token'])->first();
            if ($old) {
                if ((int) $old->hourly_rental_order_id !== (int) $order->id) HourlyOrderService::reject('رمز الطلب مستخدم لتعويض آخر.');
                return $old;
            }
            $visitId = !empty($data['visit_id']) ? (int) $data['visit_id'] : null;
            if ($visitId && !$order->visits()->whereKey($visitId)->exists()) HourlyOrderService::reject('الزيارة ليست ضمن هذا الطلب.');
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) HourlyOrderService::reject('أدخل مبلغ التعويض.');
            $limit = $this->limit($order, $visitId);
            $over = $amount > $limit;
            if ($over && !$mayExceedPaid) HourlyOrderService::reject('مبلغ التعويض يتجاوز ما دفعه العميل'.($visitId ? ' عن هذه الزيارة' : '').' بعد التعويضات السابقة ('.number_format($limit, 2).' ر.س). يتطلب ذلك صلاحية «تعويض يتجاوز المدفوع».');
            $reason = self::REASONS[$data['reason']] ?? null;
            if (!$reason) HourlyOrderService::reject('اختر سبب التعويض.');
            $note = trim((string) ($data['note'] ?? ''));
            if ($data['reason'] === 'other' && $note === '') HourlyOrderService::reject('اكتب سبب التعويض.');
            if (!isset(self::DESTINATIONS[$data['destination']])) HourlyOrderService::reject('اختر طريقة إيصال التعويض.');

            $walletTransaction = null;
            if ($data['destination'] === 'wallet') {
                $description = 'تعويض عن الطلب '.$order->order_number.' — '.$reason.($note !== '' ? ': '.$note : '');
                app(CustomerDirectory::class)->moveWallet(User::findOrFail($order->user_id), $amount,
                    ['type' => 'compensation', 'linked_order_id' => $order->id, 'payment_method' => 'wallet',
                        'description' => mb_substr($description, 0, 500), 'paid_at' => now()], $admin->id);
                $walletTransaction = DB::table('hourly_wallet_transactions')->where('user_id', $order->user_id)
                    ->where('type', 'compensation')->where('linked_order_id', $order->id)->max('id');
            }
            $id = DB::table('hourly_order_compensations')->insertGetId([
                'hourly_rental_order_id' => $order->id, 'visit_id' => $visitId, 'user_id' => $order->user_id, 'amount' => $amount,
                'reason' => $data['reason'], 'note' => $note !== '' ? $note : null, 'destination' => $data['destination'],
                'status' => $data['destination'] === 'wallet' ? 'credited' : 'pending_payout', 'wallet_transaction_id' => $walletTransaction,
                'over_paid_limit' => $over, 'created_by' => $admin->id, 'request_token' => $data['request_token'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            app(OrderManagementService::class)->audit($order, 'compensation', [], ['compensation_id' => $id, 'amount' => $amount,
                'reason' => $data['reason'], 'destination' => $data['destination'], 'over_paid_limit' => $over], $admin->id, $visitId,
                'تعويض العميل: '.$reason.($note !== '' ? ' — '.$note : ''));
            $row = DB::table('hourly_order_compensations')->find($id);
            $row->created_now = true;
            return $row;
        });
        if (!empty($row->created_now)) $this->notifyCustomer($row, $admin);
        return $row;
    }

    /** A cash or bank compensation is a promise until the cashier records that the customer received it. */
    public function markPaidOut(HourlyRentalOrder $order, int $id, ?string $reference, Admin $admin): void
    {
        DB::transaction(function () use ($order, $id, $reference, $admin) {
            $row = DB::table('hourly_order_compensations')->where('hourly_rental_order_id', $order->id)->lockForUpdate()->find($id);
            abort_unless($row, 404);
            if ($row->status === 'paid_out') return;
            if ($row->status !== 'pending_payout') HourlyOrderService::reject('هذا التعويض لا ينتظر صرفًا.');
            DB::table('hourly_order_compensations')->where('id', $id)->update(['status' => 'paid_out', 'paid_out_at' => now(),
                'paid_out_by' => $admin->id, 'payout_reference' => $reference !== null && trim($reference) !== '' ? trim($reference) : null, 'updated_at' => now()]);
            app(OrderManagementService::class)->audit($order, 'compensation_paid_out', ['status' => 'pending_payout'],
                ['compensation_id' => $id, 'status' => 'paid_out', 'reference' => $reference], $admin->id, $row->visit_id, 'تأكيد صرف التعويض للعميل');
        });
    }

    /** @return array<int, array<string, mixed>> what the customer app shows about compensations on an order */
    public function publicData(HourlyRentalOrder $order): array
    {
        return $this->forOrder($order)->map(fn ($row) => ['id' => (int) $row->id, 'amount' => (float) $row->amount,
            'reason' => $row->reason, 'reason_label' => self::REASONS[$row->reason] ?? $row->reason,
            'destination' => $row->destination, 'destination_label' => self::DESTINATIONS[$row->destination] ?? $row->destination,
            'status' => $row->status, 'status_label' => self::STATUSES[$row->status] ?? $row->status,
            'visit_id' => $row->visit_id ? (int) $row->visit_id : null, 'created_at' => \Carbon\Carbon::parse($row->created_at)->toIso8601String()])->values()->all();
    }

    private function notifyCustomer(object $row, Admin $admin): void
    {
        $order = HourlyRentalOrder::with('user')->find($row->hourly_rental_order_id);
        if (!$order?->user) return;
        $amount = number_format((float) $row->amount, 2);
        $body = $row->destination === 'wallet'
            ? "أضفنا إلى محفظتك تعويضًا بقيمة {$amount} ر.س عن الطلب {$order->order_number}. نعتذر عما حدث."
            : "سنصرف لك تعويضًا بقيمة {$amount} ر.س عن الطلب {$order->order_number} (".self::DESTINATIONS[$row->destination]."). نعتذر عما حدث.";
        try { app(CustomerNotifier::class)->send($order->user, 'app', 'تعويض عن طلبك', $body, $admin); }
        catch (\Throwable $e) { report($e); }
    }
}
