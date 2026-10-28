<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يربط بنود «تقرير حركة صندوق اليومية» برقم الحساب بدل كوده، لشركة واحدة (قاعدة البيانات الحالية).
 * يقرأ الأكواد من packages/finance/config/daily_cash_reference.php ويحفظ لكل بند الحساب الذي يحمل الكود،
 * فيبقى البند على حسابه بعد أي نقل أو إعادة ترقيم. الشركات التي لا تشغّل الأمر لا يتغير عندها شيء.
 *
 * --as-of: إن كانت الشجرة رُتّبت بعد كتابة الأكواد، يُحدَّد الحساب الذي كان يحمل الكود في ذلك الوقت
 * من سجل إعادة الهيكلة (fn_account_restructure_logs): النقل يتبع الحساب، والدمج يتبع الهدف، والحذف يترك البند غير مربوط.
 */
class LinkDailyCashReferenceCommand extends Command
{
    protected $signature = 'finance:link-daily-cash-reference
        {--as-of= : الأكواد كما كانت في هذا الوقت (Y-m-d H:i:s)، باستخدام سجل إعادة هيكلة الدليل}
        {--dry-run : عرض الربط دون حفظ}
        {--force : استبدال ربط محفوظ سابقاً}';

    protected $description = 'ربط بنود تقرير صندوق اليومية برقم الحساب بدل الكود (للشركة الحالية فقط)';

    public function handle(): int
    {
        if (!Schema::hasTable('fn_daily_cash_reference_lines')) {
            $this->error('شغّل php artisan migrate أولاً (جدول fn_daily_cash_reference_lines غير موجود).');
            return self::FAILURE;
        }
        if (DB::table('fn_daily_cash_reference_lines')->exists() && !$this->option('force') && !$this->option('dry-run')) {
            $this->error('البنود مربوطة بالفعل. استخدم --force لإعادة الربط.');
            return self::FAILURE;
        }

        $asOf = $this->option('as-of') ? Carbon::parse($this->option('as-of')) : null;
        $mapping = require base_path('packages/finance/config/daily_cash_reference.php');
        $accounts = DB::table('fn_accounts')->get(['id', 'code', 'name', 'created_at'])->keyBy('id');
        $byCode = $accounts->groupBy(fn ($a) => (string) $a->code);
        $logs = $asOf && Schema::hasTable('fn_account_restructure_logs')
            ? DB::table('fn_account_restructure_logs')->where('created_at', '>', $asOf)->whereNull('reversed_at')->orderBy('id')->get()
            : collect();

        $rows = []; $report = [];
        foreach ($mapping as $group => $codes) {
            $order = 0;
            foreach ($codes as $code => $label) {
                [$accountId, $how] = $this->resolve((string) $code, $byCode, $accounts, $logs, $asOf);
                $rows[] = ['group_key' => $group, 'sort_order' => ++$order, 'account_id' => $accountId, 'reference_code' => (string) $code, 'label' => $label];
                $account = $accountId ? $accounts->get($accountId) : null;
                $report[] = [$group, $code, $label, $account ? $account->code . ' ' . $account->name : '—', $how];
            }
        }

        $this->table(['القسم', 'الكود في الملف', 'البند', 'الحساب المربوط', 'الطريقة'], $report);
        $unlinked = count(array_filter($rows, fn ($r) => $r['account_id'] === null));
        $this->info('البنود: ' . count($rows) . ' — مربوطة: ' . (count($rows) - $unlinked) . ' — غير مربوطة: ' . $unlinked);

        if ($this->option('dry-run')) {
            $this->warn('معاينة فقط؛ لم يُحفظ شيء.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            DB::table('fn_daily_cash_reference_lines')->delete();
            $now = now();
            DB::table('fn_daily_cash_reference_lines')->insert(array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $rows));
        });
        $this->info('تم الحفظ. التقرير يقرأ البنود الآن برقم الحساب.');

        return self::SUCCESS;
    }

    /** @return array{0: ?int, 1: string} */
    private function resolve(string $code, $byCode, $accounts, $logs, ?Carbon $asOf): array
    {
        if ($asOf) {
            // أول عملية بعد التاريخ على حساب كان يحمل هذا الكود تحدد صاحبه في ذلك الوقت
            $log = $logs->first(fn ($l) => (string) $l->account_code === $code);
            if ($log) {
                if ($log->action === 'delete') return [null, 'حُذف حسابه بعد التاريخ'];
                $id = $log->action === 'merge' ? (int) $log->target_account_id : (int) $log->account_id;
                $id = $this->followMerges($id, $logs);
                return $accounts->has($id) ? [$id, $log->action === 'merge' ? 'دُمج حسابه — يتبع الهدف' : 'من سجل إعادة الهيكلة'] : [null, 'حسابه لم يعد موجوداً'];
            }
            $holder = $byCode->get($code, collect());
            if ($holder->count() !== 1) return [null, $holder->isEmpty() ? 'الكود غير موجود' : 'الكود مكرر'];
            $account = $holder->first();
            $movedHere = $logs->contains(fn ($l) => (int) $l->account_id === (int) $account->id && $l->action === 'move');
            $createdLater = $account->created_at && Carbon::parse($account->created_at)->gt($asOf);
            if ($movedHere || $createdLater) return [null, 'الكود صار لحساب آخر بعد التاريخ'];
            return [(int) $account->id, 'بالكود (لم يتغير)'];
        }

        $holder = $byCode->get($code, collect());
        if ($holder->count() !== 1) return [null, $holder->isEmpty() ? 'الكود غير موجود' : 'الكود مكرر'];
        return [(int) $holder->first()->id, 'بالكود'];
    }

    private function followMerges(int $id, $logs): int
    {
        $seen = [];
        while (!isset($seen[$id]) && ($merge = $logs->first(fn ($l) => $l->action === 'merge' && (int) $l->account_id === $id))) {
            $seen[$id] = true;
            $id = (int) $merge->target_account_id;
        }
        return $id;
    }
}
