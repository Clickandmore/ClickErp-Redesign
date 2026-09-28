<?php

namespace App\Services\Hourly;

use App\Models\Hourly\HourlyCustomerProfile;
use App\Models\ingaz\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * الحسابات المكررة ودمجها.
 * التكرار: نفس الجوال بعد التطبيع، أو نفس البريد، أو نفس رقم الهوية.
 * الدمج ينقل بيانات الساعات (الطلبات وزياراتها ودفعاتها، العناوين، رصيد المحفظة وحركاتها، التقييمات، المفضلة، بطاقات الهوية)
 * للحساب الأساسي، ثم يحذف ملف الساعات للحساب الآخر حذفًا مؤقتًا. بيانات الأنظمة الأخرى (عقود الاستقدام وغيرها) لا تُنقل،
 * وسجل العميل المشترك لا يُحذف.
 */
class MergeCustomersService
{
    /** جداول الساعات (والعناوين) التي تُنقل بتغيير user_id. الزيارات تتبع طلباتها. */
    public const MOVED_TABLES = ['hourly_rental_orders', 'hourly_order_payments', 'hourly_wallet_transactions', 'hourly_worker_reviews', 'user_addresses', 'hourly_customer_documents'];
    /** جداول سجلات بقيد فريد مع batch_id: يُنقل منها ما لا يتعارض، والباقي يبقى تاريخًا على الحساب القديم. */
    private const BATCH_TABLES = ['hourly_visit_notices' => ['batch_id', 'channel'], 'hourly_visit_receipts' => ['batch_id']];

    public function __construct(private CustomerDirectory $directory) {}

    /**
     * مجموعات العملاء المحتمل تكرارهم (بلا المحذوفين من قسم الساعات)، الأكبر أولًا.
     *
     * @return Collection<int, array{members:Collection,reasons:array}>
     */
    public function groups(?int $userId = null): Collection
    {
        $users = DB::table('users')->leftJoin('hourly_customer_profiles as p', 'p.user_id', '=', 'users.id')->whereNull('p.deleted_at')
            ->get(['users.id', 'users.name', 'users.phone', 'users.email', 'users.national_num', 'users.created_at', 'users.register_from', 'users.hourly_wallet_balance']);
        $parent = [];
        $find = function ($id) use (&$parent, &$find) { return $parent[$id] === $id ? $id : ($parent[$id] = $find($parent[$id])); };
        $owners = [];
        $reasons = $groupReasons = [];
        foreach ($users as $user) {
            $parent[$user->id] = $user->id;
            foreach ($this->keys($user) as $key) $owners[$key][] = $user->id;
        }
        foreach ($owners as $key => $ids) {
            if (count($ids) < 2) continue;
            foreach ($ids as $id) {
                [$a, $b] = [$find($ids[0]), $find($id)];
                if ($a !== $b) $parent[$b] = $a;
            }
            $reasons[$ids[0]][] = strtok($key, ':');
        }
        $groups = [];
        foreach ($users as $user) $groups[$find($user->id)][] = $user;
        foreach ($reasons as $id => $list) $groupReasons[$find($id)] = array_merge($groupReasons[$find($id)] ?? [], $list);

        $orders = DB::table('hourly_rental_orders')->select('user_id', DB::raw('COUNT(*) as n'))->groupBy('user_id')->pluck('n', 'user_id');

        return collect($groups)->filter(fn ($members) => count($members) > 1)
            ->when($userId, fn ($c) => $c->filter(fn ($members) => collect($members)->contains('id', $userId)))
            ->map(fn ($members, $root) => [
                'key' => $root, 'reasons' => array_values(array_unique($groupReasons[$root] ?? [])),
                'members' => collect($members)->map(fn ($u) => (array) $u + ['orders' => (int) ($orders[$u->id] ?? 0)])->sortBy('id')->values(),
            ])->sortByDesc(fn ($g) => $g['members']->count())->values();
    }

    /**
     * دمج حسابات في حساب أساسي داخل transaction واحدة.
     *
     * @param int[] $secondaryIds
     * @return array<int, array> ما نُقل لكل حساب مدموج
     */
    public function merge(User $primary, array $secondaryIds, int $adminId, ?string $reason = null): array
    {
        $secondaryIds = array_values(array_unique(array_map('intval', $secondaryIds)));
        if (!$secondaryIds || in_array($primary->id, $secondaryIds, true)) throw ValidationException::withMessages(['merge' => __('hourly_customers.merge.invalid')]);

        return DB::transaction(function () use ($primary, $secondaryIds, $adminId, $reason) {
            $ids = array_merge([$primary->id], $secondaryIds);
            sort($ids);
            $users = User::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($users->count() !== count($ids)) throw ValidationException::withMessages(['merge' => __('hourly_customers.merge.invalid')]);
            $primaryProfile = HourlyCustomerProfile::withTrashed()->where('user_id', $primary->id)->first();
            if ($primaryProfile?->trashed()) throw ValidationException::withMessages(['merge' => __('hourly_customers.merge.primary_deleted')]);

            $summary = [];
            foreach ($secondaryIds as $secondaryId) {
                $secondary = $users[$secondaryId];
                if (HourlyCustomerProfile::onlyTrashed()->where('user_id', $secondaryId)->exists()) throw ValidationException::withMessages(['merge' => __('hourly_customers.merge.already', ['id' => $secondaryId])]);
                $moved = [];
                foreach (self::MOVED_TABLES as $table) $moved[$table] = DB::table($table)->where('user_id', $secondaryId)->update(['user_id' => $primary->id]);
                foreach (self::BATCH_TABLES as $table => $unique) {
                    $moved[$table] = DB::table($table.' as s')->where('s.user_id', $secondaryId)
                        ->whereNotExists(function ($q) use ($table, $unique, $primary) {
                            $q->from($table.' as t')->where('t.user_id', $primary->id)->select(DB::raw(1));
                            foreach ($unique as $column) $q->whereColumn('t.'.$column, 's.'.$column);
                        })->pluck('s.id')->pipe(fn ($rows) => $rows->isEmpty() ? 0 : DB::table($table)->whereIn('id', $rows)->update(['user_id' => $primary->id]));
                }
                $moved['favorites'] = $this->moveFavorites($secondaryId, $primary->id);

                // الرصيد: حركات الحساب القديم انتقلت كتاريخ، وقيمته تُضاف للأساسي بحركة «دمج» واحدة.
                $balance = round((float) $secondary->hourly_wallet_balance, 2);
                if (abs($balance) >= 0.005) {
                    DB::table('users')->where('id', $secondaryId)->update(['hourly_wallet_balance' => 0]);
                    $this->directory->moveWallet($primary, $balance, ['type' => 'merge', 'description' => __('hourly_customers.merge.ledger', ['id' => $secondaryId])], $adminId);
                }
                $moved['balance'] = $balance;
                $this->fillMissing($primary, $secondary, $primaryProfile);

                $this->directory->delete($secondary, $adminId, $reason, $primary->id);
                DB::table('hourly_customer_merges')->insert(['primary_user_id' => $primary->id, 'merged_user_id' => $secondaryId, 'admin_id' => $adminId,
                    'moved' => json_encode($moved), 'reason' => $reason, 'created_at' => now()]);
                $this->directory->log($primary, 'customers_merged', ['merged_user_id' => $secondaryId, 'moved' => $moved, 'reason' => $reason], $adminId);
                $summary[$secondaryId] = $moved;
            }

            return $summary;
        });
    }

    /** مفاتيح المقارنة: الجوال المطبّع، والبريد بحروف صغيرة، ورقم الهوية (8 أرقام فأكثر). */
    private function keys(object $user): array
    {
        $keys = [];
        if ($phone = PhoneNumber::key($user->phone)) $keys[] = 'phone:'.$phone;
        $email = mb_strtolower(trim((string) $user->email));
        if ($email !== '' && str_contains($email, '@')) $keys[] = 'email:'.$email;
        $national = preg_replace('/\D/', '', (string) $user->national_num);
        if (strlen($national) >= 8) $keys[] = 'national:'.$national;

        return $keys;
    }

    private function moveFavorites(int $from, int $to): int
    {
        $rows = DB::table('hourly_customer_favorites')->where('user_id', $from)->get();
        foreach ($rows as $row) {
            DB::table('hourly_customer_favorites')->insertOrIgnore(['user_id' => $to, 'favorable_type' => $row->favorable_type, 'favorable_id' => $row->favorable_id,
                'created_by' => $row->created_by, 'created_at' => $row->created_at, 'updated_at' => now()]);
        }
        DB::table('hourly_customer_favorites')->where('user_id', $from)->delete();

        return $rows->count();
    }

    /** يكمل الناقص في الحساب الأساسي من المدموج (بلا الكتابة فوق ما هو موجود، والبريد لا يُنقل لأنه فريد). */
    private function fillMissing(User $primary, User $secondary, ?HourlyCustomerProfile $profile): void
    {
        $shared = [];
        foreach (['national_num', 'birthdate'] as $field) if (blank($primary->$field) && filled($secondary->$field)) $shared[$field] = $secondary->$field;
        if (blank($primary->phone2) && filled($secondary->phone) && PhoneNumber::key($secondary->phone) !== PhoneNumber::key($primary->phone)) $shared['phone2'] = $secondary->phone;
        if ($shared) $primary->update($shared);

        $old = HourlyCustomerProfile::where('user_id', $secondary->id)->first();
        if ($profile && $old) {
            $fill = [];
            foreach (['iban', 'identity_type', 'marketing_source', 'group_id', 'payment_term', 'note'] as $field) if (blank($profile->$field) && filled($old->$field)) $fill[$field] = $old->$field;
            if ($fill) $profile->update($fill);
        }
    }
}
