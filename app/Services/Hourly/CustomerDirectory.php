<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyCustomerFavorite, HourlyCustomerProfile, HourlyRentalOrder};
use App\Models\ingaz\User;
use App\Support\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * عملاء قسم الساعات: نفس جدول users لكل الأنظمة، مع بيانات الساعات في hourly_customer_profiles.
 * هذه الخدمة لا تكتب في users إلا الحقول المشتركة (الاسم، الجوال، البريد، الهوية، الميلاد، الهاتف 2)
 * وكلمة المرور عند طلبها صراحة، ولا تمس الحظر أو حقول الاستقدام.
 */
class CustomerDirectory
{
    public const BALANCE_STATES = ['all', 'positive', 'negative', 'non_zero'];
    public const ACTIVITY = ['all', 'inactive_2m', 'no_orders'];
    public const PAYMENT_METHODS = ['cash', 'card_on_delivery', 'bank_transfer', 'cheque'];
    /** أعمدة الترتيب المسموحة في القائمة => العمود أو الاسم المستعار في الاستعلام. */
    public const SORTS = ['created_at' => 'users.created_at', 'balance' => 'users.hourly_wallet_balance', 'orders_count' => 'orders_count', 'orders_total' => 'orders_total'];
    /** طلبات قائمة تمنع حذف العميل. */
    public const OPEN_ORDERS = ['pending_confirmation', 'active'];
    /** الطلبات التي لا تُحسب في عدد وإجمالي طلبات العميل. */
    private const VOID_ORDERS = ['cancelled', 'expired'];

    /** قائمة العملاء بعد الفلاتر، مع عدد وإجمالي طلبات الساعات والطلبات المكتملة والمجموعة. */
    public function query(array $f): Builder
    {
        $orders = fn ($q) => $q->from('hourly_rental_orders as o')->whereColumn('o.user_id', 'users.id')->whereNotIn('o.status', self::VOID_ORDERS);

        return User::query()
            ->leftJoin('hourly_customer_profiles as p', 'p.user_id', '=', 'users.id')
            ->leftJoin('hourly_customer_groups as g', 'g.id', '=', 'p.group_id')
            ->select('users.id', 'users.name', 'users.email', 'users.phone', 'users.phone_code', 'users.created_at', 'users.register_from', 'users.hourly_wallet_balance', 'users.is_blocked',
                'p.customer_type', 'p.marketing_source', 'p.verified_at', 'p.deleted_at as hourly_deleted_at', 'p.merged_into_id', 'g.name as group_name')
            ->selectSub(fn ($q) => $orders($q)->selectRaw('COUNT(*)'), 'orders_count')
            ->selectSub(fn ($q) => $orders($q)->selectRaw('COALESCE(SUM(o.total_amount), 0)'), 'orders_total')
            ->selectSub(fn ($q) => $q->from('hourly_rental_orders as oc')->whereColumn('oc.user_id', 'users.id')->where('oc.status', 'completed')->selectRaw('COUNT(*)'), 'completed_count')
            // المحذوف من قسم الساعات يختفي إلا مع «إظهار المحذوفة».
            ->when(empty($f['show_deleted']), fn ($q) => $q->whereNull('p.deleted_at'))
            ->when($f['search'] ?? null, function ($q, $s) {
                $like = '%'.trim($s).'%';
                $mobile = PhoneNumber::saudiMobile($s);
                $q->where(fn ($w) => $w->where('users.name', 'like', $like)->orWhere('users.email', 'like', $like)->orWhere('users.phone', 'like', $like)
                    ->orWhere('users.phone2', 'like', $like)->orWhere('users.national_num', 'like', $like)
                    ->when($mobile, fn ($m) => $m->orWhereIn('users.phone', PhoneNumber::variants($mobile)))
                    ->orWhere('users.id', ltrim(preg_replace('/\D/', '', $s), '0') ?: -1));
            })
            ->when(($f['balance'] ?? 'all') !== 'all', fn ($q) => match ($f['balance']) {
                'positive' => $q->where('users.hourly_wallet_balance', '>', 0),
                'negative' => $q->where('users.hourly_wallet_balance', '<', 0),
                default => $q->where('users.hourly_wallet_balance', '!=', 0),
            })
            // بلا ملف ساعات = فرد (العميل المسجّل من الأنظمة الأخرى).
            ->when($f['type'] ?? null, fn ($q, $type) => $type === 'company' ? $q->where('p.customer_type', 'company')
                : $q->where(fn ($w) => $w->whereNull('p.customer_type')->orWhere('p.customer_type', 'individual')))
            ->when(($f['activity'] ?? 'all') === 'no_orders', fn ($q) => $q->whereNotExists(fn ($s) => $orders($s)->select(DB::raw(1))))
            ->when(($f['activity'] ?? 'all') === 'inactive_2m', fn ($q) => $q->whereExists(fn ($s) => $orders($s)->select(DB::raw(1)))
                ->whereNotExists(fn ($s) => $orders($s)->where('o.created_at', '>=', now()->subMonths(2))->select(DB::raw(1))))
            ->when($f['group_id'] ?? null, fn ($q, $group) => $group === 'none' ? $q->whereNull('p.group_id') : $q->where('p.group_id', $group))
            ->when($f['marketing_source'] ?? null, fn ($q, $source) => $source === 'none' ? $q->whereNull('p.marketing_source') : $q->where('p.marketing_source', $source))
            ->when($f['register_from'] ?? null, fn ($q, $source) => $source === 'none'
                ? $q->where(fn ($w) => $w->whereNull('users.register_from')->orWhere('users.register_from', '')) : $q->where('users.register_from', $source))
            ->when($f['city_id'] ?? null, fn ($q, $id) => $q->whereExists(fn ($s) => $s->from('user_addresses as a')->whereColumn('a.user_id', 'users.id')->where('a.city_id', $id)->select(DB::raw(1))))
            ->when($f['district_id'] ?? null, fn ($q, $id) => $q->whereExists(fn ($s) => $s->from('user_addresses as a')->whereColumn('a.user_id', 'users.id')->where('a.hourly_district_id', $id)->select(DB::raw(1))))
            ->when(isset($f['verified']) && $f['verified'] !== '', fn ($q) => $f['verified'] ? $q->whereNotNull('p.verified_at') : $q->whereNull('p.verified_at'));
    }

    /** الترتيب بالضغط على رأس العمود، والافتراضي الأحدث أولًا. */
    public function sort(Builder $query, array $f): Builder
    {
        $column = self::SORTS[$f['sort'] ?? ''] ?? null;
        if (!$column) return $query->orderByDesc('users.id');

        return $query->orderBy($column, ($f['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc')->orderByDesc('users.id');
    }

    /** «Positive / Negative»: مجموع الأرصدة الموجبة والسالبة ضمن الفلاتر. */
    public function balances(Builder $query): array
    {
        $row = DB::query()->fromSub((clone $query)->reorder(), 'c')
            ->selectRaw('COALESCE(SUM(CASE WHEN hourly_wallet_balance > 0 THEN hourly_wallet_balance ELSE 0 END), 0) as positive')
            ->selectRaw('COALESCE(SUM(CASE WHEN hourly_wallet_balance < 0 THEN hourly_wallet_balance ELSE 0 END), 0) as negative')->first();

        return ['positive' => (float) $row->positive, 'negative' => (float) $row->negative];
    }

    /** ملف الساعات للعميل حتى لو كان محذوفًا؛ null إن لم يُنشأ بعد. */
    public function profile(User $user): ?HourlyCustomerProfile
    {
        return HourlyCustomerProfile::withTrashed()->where('user_id', $user->id)->first();
    }

    /**
     * إنشاء عميل أو تعديل عميل موجود (من أي نظام) وحفظ ملف الساعات له.
     * الجوال يُطبَّع إلى 5XXXXXXXX إن كان سعوديًا (الجوال القديم بصيغة غير سعودية يبقى كما هو).
     */
    public function save(?User $user, array $data, int $adminId): User
    {
        return DB::transaction(function () use ($user, $data, $adminId) {
            $company = $data['customer_type'] === 'company';
            $phone2 = isset($data['phone2']) && $data['phone2'] !== '' ? (PhoneNumber::saudiMobile($data['phone2']) ?? $data['phone2']) : null;
            $shared = [
                'name' => $company ? $data['company_name'] : trim($data['first_name'].' '.$data['last_name']),
                'phone' => PhoneNumber::saudiMobile($data['phone']) ?? $data['phone'], 'email' => $data['email'] ?? null, 'phone2' => $phone2,
                'national_num' => $data['national_num'] ?? null, 'birthdate' => $data['birthdate'] ?? null,
            ];
            if ($user) {
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $user->update($shared);
                if (!empty($data['new_password'])) $this->changePassword($user, $data['new_password'], $adminId);
            } else {
                $user = User::create($shared + [
                    'phone_code' => '966', 'register_from' => 'admin', 'is_blocked' => 0, 'created_by' => $adminId,
                    // الدخول للتطبيق يكون بكلمة يضعها الموظف أو يعيد العميل تعيينها لاحقًا.
                    'password' => Hash::make($data['password'] ?? Str::random(40)),
                ]);
            }
            HourlyCustomerProfile::withTrashed()->updateOrCreate(['user_id' => $user->id], [
                'customer_type' => $data['customer_type'], 'group_id' => $data['group_id'] ?? null, 'payment_term' => $data['payment_term'] ?? null,
                'first_name' => $company ? null : $data['first_name'], 'last_name' => $company ? null : $data['last_name'],
                'company_name' => $company ? $data['company_name'] : null, 'company_id' => $company ? ($data['company_id'] ?? null) : null,
                'identity_type' => $data['identity_type'] ?? null, 'language' => $data['language'] ?? 'ar',
                'iban' => $data['iban'] ?? null, 'note' => $data['note'] ?? null, 'marketing_source' => $data['marketing_source'] ?? null,
            ]);

            return $user;
        });
    }

    /**
     * كلمة المرور تُكتب مباشرة في users حتى لا تمر بقيمتها (ولو مشفّرة) على سجل النشاط،
     * ويُسجَّل الحدث وحده بلا القيمة.
     */
    public function changePassword(User $user, string $password, int $adminId): void
    {
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($password), 'updated_at' => now()]);
        $this->log($user, 'password_changed', [], $adminId);
    }

    /** الجوال مسجل لعميل آخر بأي صيغة (5… أو 05… أو 966… أو ‎+966…). */
    public function phoneTaken(string $phone, ?int $except = null): bool
    {
        $mobile = PhoneNumber::saudiMobile($phone);
        $candidates = $mobile ? PhoneNumber::variants($mobile) : [$phone];

        return User::whereIn('phone', $candidates)->when($except, fn ($q) => $q->where('id', '!=', $except))->exists();
    }

    public function setVerified(User $user, bool $verified, int $adminId): HourlyCustomerProfile
    {
        $profile = $this->ensureProfile($user);
        $profile->fill(['verified_at' => $verified ? now() : null, 'verified_by' => $verified ? $adminId : null])->save();

        return $profile;
    }

    /** أسباب منع الحذف: طلبات قائمة أو رصيد غير صفري. */
    public function deleteBlockers(User $user): array
    {
        $reasons = [];
        $open = HourlyRentalOrder::where('user_id', $user->id)->whereIn('status', self::OPEN_ORDERS)->count();
        if ($open) $reasons[] = __('hourly_customers.delete.open_orders', ['count' => $open]);
        if (abs((float) $user->hourly_wallet_balance) >= 0.005) $reasons[] = __('hourly_customers.delete.balance', ['amount' => number_format((float) $user->hourly_wallet_balance, 2)]);

        return $reasons;
    }

    /** حذف مؤقت لملف الساعات فقط؛ سجل العميل المشترك يبقى للأنظمة الأخرى. */
    public function delete(User $user, int $adminId, ?string $reason = null, ?int $mergedInto = null): HourlyCustomerProfile
    {
        return DB::transaction(function () use ($user, $adminId, $reason, $mergedInto) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (!$mergedInto && ($blockers = $this->deleteBlockers($user))) throw ValidationException::withMessages(['delete' => implode(' ', $blockers)]);
            $profile = $this->ensureProfile($user);
            if ($profile->trashed()) throw ValidationException::withMessages(['delete' => __('hourly_customers.delete.already')]);
            $profile->fill(['deleted_by' => $adminId, 'merged_into_id' => $mergedInto])->save();
            $profile->delete();
            $this->log($user, $mergedInto ? 'hourly_merged_into' : 'hourly_deleted', array_filter(['reason' => $reason, 'merged_into' => $mergedInto]), $adminId);

            return $profile;
        });
    }

    public function restore(User $user, int $adminId): HourlyCustomerProfile
    {
        return DB::transaction(function () use ($user, $adminId) {
            $profile = HourlyCustomerProfile::withTrashed()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$profile || !$profile->trashed()) throw ValidationException::withMessages(['restore' => __('hourly_customers.delete.not_deleted')]);
            // حساب مدموج نُقلت بياناته لحساب آخر؛ استعادته تُظهر حسابًا فارغًا وتعيد التكرار.
            if ($profile->merged_into_id) throw ValidationException::withMessages(['restore' => __('hourly_customers.delete.merged', ['id' => $profile->merged_into_id])]);
            $profile->restore();
            $profile->update(['deleted_by' => null]);
            $this->log($user, 'hourly_restored', [], $adminId);

            return $profile;
        });
    }

    /**
     * «ADD BALANCE»: إيداع في محفظة العميل بلا ربط، أو دفعة على طلب محدد من طلباته.
     * الدفعة على الطلب تمر عبر HourlyPaymentService::collect القائمة (طريقة الدفع المسموحة، المتبقي، إثبات التحويل).
     */
    public function deposit(User $user, array $data, int $adminId): array
    {
        return DB::transaction(function () use ($user, $data, $adminId) {
            $amount = round((float) $data['amount'], 2);
            $collector = ['collected_by_type' => $data['collected_by_type'], 'collected_by_id' => (int) $data['collected_by_id']];
            $note = isset($data['description']) ? mb_substr(trim(strip_tags($data['description'])), 0, 500) : null;

            if ($data['mode'] === 'order') {
                $order = HourlyRentalOrder::where('user_id', $user->id)->findOrFail($data['order_id']);
                $payment = app(HourlyPaymentService::class)->collect($order, ['amount' => $amount, 'collection_method' => $data['payment_method'],
                    'paid_at' => $data['paid_at'], 'attachment_path' => $data['attachment_path'] ?? null], $adminId);
                $payment->update($collector + ['provider_payload' => ['type' => 'customer_profile_payment', 'vat_rate' => (float) $data['vat_rate'], 'description' => $note, 'entered_by' => $adminId]]);
                app(OrderManagementService::class)->audit($order, 'customer_payment', [], ['payment_id' => $payment->id, 'amount' => $amount] + $collector, $adminId, null, $note);

                return ['type' => 'order', 'order_id' => $order->id, 'order_number' => $order->order_number, 'payment_id' => $payment->id, 'amount' => $amount];
            }

            $balance = $this->moveWallet($user, $amount, $collector + ['type' => 'deposit', 'payment_method' => $data['payment_method'], 'vat_rate' => $data['vat_rate'], 'description' => $note, 'paid_at' => $data['paid_at']], $adminId);

            return ['type' => 'wallet', 'balance' => $balance, 'amount' => $amount];
        });
    }

    /** «تحرير» الرصيد: زيادة أو خصم يدوي بسبب إلزامي، يُقيَّد كحركة «تسوية». الرصيد قد يصبح سالبًا (مديونية). */
    public function adjust(User $user, string $direction, float $amount, string $reason, int $adminId): float
    {
        return DB::transaction(function () use ($user, $direction, $amount, $reason, $adminId) {
            $signed = round($direction === 'decrease' ? -$amount : $amount, 2);
            $balance = $this->moveWallet($user, $signed, ['type' => 'adjustment', 'description' => mb_substr(trim(strip_tags($reason)), 0, 500), 'paid_at' => now()], $adminId);
            $this->log($user, 'balance_adjusted', ['direction' => $direction, 'amount' => $amount, 'balance_after' => $balance, 'reason' => $reason], $adminId);

            return $balance;
        });
    }

    /** يحرّك رصيد المحفظة ويكتب حركتها في نفس الـ transaction. */
    public function moveWallet(User $user, float $amount, array $row, ?int $adminId): float
    {
        $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        $balance = round((float) $locked->hourly_wallet_balance + $amount, 2);
        $locked->update(['hourly_wallet_balance' => $balance]);
        DB::table('hourly_wallet_transactions')->insert($row + ['user_id' => $user->id, 'amount' => round($amount, 2), 'balance_after' => $balance, 'admin_id' => $adminId, 'created_at' => now(), 'updated_at' => now()]);

        return $balance;
    }

    /** العمود الجانبي في ملف العميل. */
    public function stats(User $user): array
    {
        $orders = HourlyRentalOrder::where('user_id', $user->id);
        $firstOrder = (clone $orders)->orderBy('created_at')->first(['id', 'created_at']);
        $lastOrder = (clone $orders)->orderByDesc('created_at')->first(['id', 'created_at']);
        $visitDate = fn (?HourlyRentalOrder $order, string $dir) => $order ? DB::table('hourly_visits')->where('hourly_rental_order_id', $order->id)
            ->whereNotIn('status', VisitSearch::CANCELLED)->orderBy('scheduled_start_at', $dir)->value('scheduled_start_at') : null;

        return [
            'balance' => (float) $user->hourly_wallet_balance,
            'deposits' => (float) DB::table('hourly_wallet_transactions')->where('user_id', $user->id)->where('type', 'deposit')->sum('amount'),
            // الإيرادات = كل ما سُدِّد على طلبات العميل بعد خصم المسترد.
            'revenue' => (float) DB::table('hourly_order_payments')->where('user_id', $user->id)->where('status', 'paid')->sum('amount'),
            'first_order' => $firstOrder?->created_at, 'first_visit' => $visitDate($firstOrder, 'asc'),
            'last_order' => $lastOrder?->created_at, 'last_visit' => $visitDate($lastOrder, 'desc'),
            'orders' => (clone $orders)->whereNotIn('status', self::VOID_ORDERS)->count(),
        ];
    }

    /**
     * بطاقات الهيدر: مكتمل / قيد الانتظار / ملغي — لكل منها عدد الطلبات بحالتها، وعدد الزيارات بحالتها ومجموع ساعاتها.
     *
     * @return array<string, array{orders:int,visits:int,hours:float}>
     */
    public function buckets(User $user): array
    {
        $orderBuckets = ['completed' => ['completed'], 'pending' => self::OPEN_ORDERS, 'cancelled' => ['cancelled', 'expired']];
        $visitBuckets = ['completed' => ['completed'], 'pending' => null, 'cancelled' => array_merge(VisitSearch::CANCELLED, ['no_show'])];
        $visits = fn () => DB::table('hourly_visits as v')->join('hourly_rental_orders as o', 'o.id', '=', 'v.hourly_rental_order_id')->where('o.user_id', $user->id);
        $result = [];
        foreach ($orderBuckets as $key => $statuses) {
            $query = $visitBuckets[$key] ? $visits()->whereIn('v.status', $visitBuckets[$key]) : $visits()->whereNotIn('v.status', VisitSearch::CLOSED);
            $row = $query->selectRaw('COUNT(*) as n, COALESCE(SUM(v.planned_hours), 0) as h')->first();
            $result[$key] = ['orders' => HourlyRentalOrder::where('user_id', $user->id)->whereIn('status', $statuses)->count(), 'visits' => (int) $row->n, 'hours' => round((float) $row->h, 2)];
        }

        return $result;
    }

    /**
     * كشف حساب بين تاريخين. الرصيد هنا = المسدَّد − المستحق (موجب = للعميل، سالب = عليه).
     * المستحق: إجمالي الطلبات (الملغاة بقيمة رسوم الإلغاء كما يحسبها الطلب؛ المنتهية غير المفعّلة لا تُحتسب).
     * الدفعات: المدفوعة على الطلبات؛ السالبة منها استردادات. والمحفظة تُعرض بافتتاحيها وختاميها أيضًا.
     */
    public function statement(User $user, Carbon $from, Carbon $to): array
    {
        [$start, $end] = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
        $orders = HourlyRentalOrder::where('user_id', $user->id)->where('status', '!=', 'expired');
        $payments = DB::table('hourly_order_payments')->where('hourly_order_payments.user_id', $user->id)->where('hourly_order_payments.status', 'paid');
        $paidAt = DB::raw('COALESCE(hourly_order_payments.paid_at, hourly_order_payments.created_at)');
        $opening = (float) (clone $payments)->where($paidAt, '<', $start)->sum('hourly_order_payments.amount') - (float) (clone $orders)->where('created_at', '<', $start)->sum('total_amount');

        $lines = collect();
        foreach ((clone $orders)->whereBetween('created_at', [$start, $end])->get(['id', 'order_number', 'total_amount', 'status', 'created_at']) as $order) {
            $lines->push(['at' => $order->created_at, 'kind' => 'order', 'ref' => '#'.$order->order_number, 'debit' => (float) $order->total_amount, 'credit' => 0.0]);
        }
        $paymentRows = (clone $payments)->where($paidAt, '>=', $start)->where($paidAt, '<=', $end)
            ->join('hourly_rental_orders as o', 'o.id', '=', 'hourly_order_payments.hourly_rental_order_id')
            ->get(['hourly_order_payments.amount', 'hourly_order_payments.method', 'hourly_order_payments.paid_at', 'hourly_order_payments.created_at', 'o.order_number']);
        foreach ($paymentRows as $p) {
            $amount = (float) $p->amount;
            $lines->push(['at' => Carbon::parse($p->paid_at ?? $p->created_at), 'kind' => $amount < 0 ? 'refund' : 'payment', 'ref' => '#'.$p->order_number.' · '.__('hourly_customers.methods.'.$p->method),
                'debit' => $amount < 0 ? -$amount : 0.0, 'credit' => $amount > 0 ? $amount : 0.0]);
        }
        $running = $opening;
        $lines = $lines->sortBy(fn ($l) => $l['at']->timestamp)->values()->map(function ($line) use (&$running) {
            $running = round($running + $line['credit'] - $line['debit'], 2);

            return $line + ['balance' => $running];
        });
        $walletAt = fn (Carbon $moment) => (float) (DB::table('hourly_wallet_transactions')->where('user_id', $user->id)->where('created_at', '<', $moment)->orderByDesc('id')->value('balance_after') ?? 0);

        return [
            'from' => $start, 'to' => $end, 'opening' => round($opening, 2), 'closing' => round($running, 2), 'lines' => $lines,
            'totals' => ['orders' => round($lines->where('kind', 'order')->sum('debit'), 2), 'payments' => round($lines->where('kind', 'payment')->sum('credit'), 2), 'refunds' => round($lines->where('kind', 'refund')->sum('debit'), 2)],
            'wallet' => ['opening' => $walletAt($start), 'closing' => $walletAt($end->copy()->addSecond())],
        ];
    }

    /** العاملات والفرق المفضلة للعميل. */
    public function favoriteIds(int $userId, string $type): array
    {
        return HourlyCustomerFavorite::where('user_id', $userId)->where('favorable_type', $type)->pluck('favorable_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * يرتّب قائمة عاملات مرشحات بحيث تأتي المفضلات (ثم عاملات الفرق المفضلة) أولًا، ويحافظ على الترتيب الأصلي داخل كل مجموعة.
     */
    public function favoritesFirst(Collection $maids, ?int $userId): Collection
    {
        if (!$userId || $maids->isEmpty() || !\Illuminate\Support\Facades\Schema::hasTable('hourly_customer_favorites')) return $maids;
        $maidIds = $this->favoriteIds($userId, 'maid');
        $teamIds = $this->favoriteIds($userId, 'team');
        if (!$maidIds && !$teamIds) return $maids;
        $teamMaids = $teamIds ? DB::table('hourly_team_members')->where('member_type', 'maid')->whereNull('left_at')->whereIn('hourly_team_id', $teamIds)->pluck('member_id')->map(fn ($id) => (int) $id)->all() : [];
        $rank = fn ($maid) => in_array((int) $maid->id, $maidIds, true) ? 0 : (in_array((int) $maid->id, $teamMaids, true) ? 1 : 2);

        return $maids->values()->map(fn ($maid, $i) => [$rank($maid), $i, $maid])->sort(fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]])->map(fn ($r) => $r[2])->values();
    }

    /** سجل نشاط لحدث على العميل (يظهر في تبويب «السجلات»). */
    public function log(User $user, string $event, array $properties, ?int $adminId): void
    {
        $admin = $adminId ? \App\Models\auth\Admin::find($adminId) : null;
        $logger = activity('hourly_customers')->performedOn($user)->withProperties($properties);
        if ($admin) $logger->causedBy($admin);
        $logger->event($event)->log($event);
    }

    private function ensureProfile(User $user): HourlyCustomerProfile
    {
        $profile = HourlyCustomerProfile::withTrashed()->firstOrNew(['user_id' => $user->id]);
        if (!$profile->exists) {
            [$first, $last] = array_pad(preg_split('/\s+/u', trim((string) $user->name), 2), 2, '');
            $profile->fill(['customer_type' => 'individual', 'language' => 'ar', 'first_name' => $first ?: null, 'last_name' => $last ?: null])->save();
        }

        return $profile;
    }
}
