<?php

namespace App\Console\Commands;

use App\Models\RentalContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يتراجع عن migrate:legacy-rental-contracts.
 *
 * لا يستعيد نسخة احتياطية — يحذف بدقة الصفوف التي أنشأها الأمر فقط،
 * معتمدًا على وسم rental_contracts.legacy_source، حتى لا يمسّ أي نشاط آخر
 * حدث على القاعدة بعد النقل.
 *
 * الوضع الافتراضي محاكاة.
 */
class UndoLegacyRentalContracts extends Command
{
    protected $signature = 'migrate:legacy-rental-contracts-undo
        {--source=aqro_old_backup : وسم المصدر المستخدم عند النقل}
        {--apply : تنفيذ فعلي وحذف. بدونه محاكاة فقط}
        {--keep-client : الإبقاء على العميل وحسابه المحاسبي}';

    protected $description = 'التراجع عن نقل عقود الإيجار القديمة وحذف ما أنشأه الأمر فقط';

    private string $source;
    /** @var array<int,array{table:string,ids:array,note:string}> */
    private array $plan = [];

    public function handle(): int
    {
        $this->source = (string) $this->option('source');
        $apply = (bool) $this->option('apply');

        $this->line('');
        $this->info($apply
            ? '=== تنفيذ فعلي — سيتم الحذف ==='
            : '=== محاكاة — سيتم التراجع عن كل شيء في النهاية ===');
        $this->line('');

        $contracts = RentalContract::query()
            ->where('legacy_source', 'like', $this->source . ':%')
            ->get(['id', 'contract_number', 'legacy_source', 'user_id']);

        if ($contracts->isEmpty()) {
            $this->warn('لا توجد عقود موسومة بـ ' . $this->source . ' — لا شيء للتراجع عنه.');
            return self::SUCCESS;
        }

        $contractIds = $contracts->pluck('id')->all();

        foreach ($contracts as $c) {
            $this->line(sprintf('  %s (id %d)  ←  %s', $c->contract_number, $c->id, $c->legacy_source));
        }
        $this->line('');

        $invoices = DB::table('rental_contract_invoices')
            ->whereIn('rental_contract_id', $contractIds)->get();

        $constraintIds = $invoices
            ->flatMap(fn ($i) => [$i->invoice_constraint_id ?? null, $i->receipt_constraint_id ?? null])
            ->filter()->unique()->values()->all();

        $bondIds = $invoices->pluck('receipt_bond_id')->filter()->unique()->values()->all();

        // table_related_id وحده لا يكفي: عمود table_name عديم الفائدة (قيمته
        // 'fn_cost_centers' للجميع)، وعقد الاستقدام رقم 316 يختلف عن عقد التأجير 316.
        // العنوان يحمل رقم العقد الفريد، فنطابق عليه أيضًا.
        $costCenterIds = [];
        if (Schema::hasTable('fn_cost_centers')) {
            $titles = $contracts
                ->map(fn ($c) => 'عقد تأجير #' . ($c->contract_number ?: $c->id))
                ->all();

            $costCenterIds = DB::table('fn_cost_centers')
                ->whereIn('table_related_id', $contractIds)
                ->whereIn('title', $titles)
                ->pluck('id')
                ->all();
        }

        // العميل: يُحذف فقط إن كان قد أُنشئ بهذا النقل ولا يملك أي بيانات أخرى
        $clientUserId = $this->resolveMigratedClient($contracts->pluck('user_id')->unique()->all(), $contractIds);

        // الترتيب مهم: الأبناء قبل الآباء
        $this->add('fn_constraint_details', $this->idsWhereIn('fn_constraint_details', 'constraint_id', $constraintIds), 'سطور القيود');
        $this->add('fn_constraint_logs', $this->idsWhereIn('fn_constraint_logs', 'constraint_id', $constraintIds), 'سجل القيود');
        $this->add('fn_bonds', $bondIds, 'سندات القبض');
        $this->add('rental_contract_invoices', $invoices->pluck('id')->all(), 'الفواتير');
        $this->add('fn_constraints', $constraintIds, 'القيود');
        $this->add('fn_cost_centers', $costCenterIds, 'مراكز التكلفة');
        $this->add('rental_contract_infos', $this->idsWhereIn('rental_contract_infos', 'rental_contract_id', $contractIds), 'بيانات العقود');

        foreach (['rental_contract_extensions', 'rental_contract_expiry_reminders', 'rental_financial_adjustments'] as $child) {
            $this->add($child, $this->idsWhereIn($child, 'rental_contract_id', $contractIds), $child);
        }

        $this->add('rental_contracts', $contractIds, 'العقود');

        if ($clientUserId && !$this->option('keep-client')) {
            $user = DB::table('users')->where('id', $clientUserId)->first(['id', 'name', 'account_id']);
            $this->add('fn_clients', $this->idsWhereIn('fn_clients', 'account_id', [$user->account_id]), 'عميل المالية');
            $this->add('users', [$clientUserId], 'العميل: ' . $user->name);
            $this->add('fn_accounts', array_filter([$user->account_id]), 'حساب العميل');
        }

        $this->table(
            ['الجدول', 'عدد الصفوف', 'المعرّفات', 'الوصف'],
            collect($this->plan)->map(fn ($p) => [
                $p['table'],
                count($p['ids']),
                count($p['ids']) ? implode(', ', array_slice($p['ids'], 0, 10)) . (count($p['ids']) > 10 ? '…' : '') : '—',
                $p['note'],
            ])->all()
        );

        $total = collect($this->plan)->sum(fn ($p) => count($p['ids']));
        $this->line('');
        $this->line("إجمالي الصفوف التي ستُحذف: {$total}");

        if (!$apply) {
            $this->line('');
            $this->warn('محاكاة — لم يُحذف شيء.');
            $this->line('أعد التشغيل مع --apply للتنفيذ الفعلي.');
            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            foreach ($this->plan as $step) {
                if (!$step['ids']) {
                    continue;
                }
                DB::table($step['table'])->whereIn('id', $step['ids'])->delete();
            }

            $remaining = RentalContract::query()
                ->where('legacy_source', 'like', $this->source . ':%')->count();

            if ($remaining > 0) {
                throw new \RuntimeException("ما زالت هناك {$remaining} عقود موسومة بعد الحذف.");
            }

            DB::commit();
            $this->line('');
            $this->info('تم التراجع والحذف بنجاح.');
            $this->line('ملاحظة: عدّادات AUTO_INCREMENT لا تعود للخلف، لكن أرقام العقود');
            $this->line('والأكواد المحاسبية تُحسب من أعلى صف موجود، فتعود لقيمها السابقة تلقائيًا.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->line('');
            $this->error('فشل وتم التراجع — لم يُحذف شيء:');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * يتحقق أن العميل أُنشئ بهذا النقل فعلًا ولا يملك أي بيانات أخرى قبل ترشيحه للحذف.
     */
    private function resolveMigratedClient(array $userIds, array $contractIds): ?int
    {
        foreach ($userIds as $userId) {
            $otherContracts = RentalContract::query()
                ->where('user_id', $userId)
                ->whereNotIn('id', $contractIds)
                ->count();

            if ($otherContracts > 0) {
                continue; // عميل قائم من قبل — لا يُحذف
            }

            foreach (['orders', 'contracts'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')
                    && DB::table($table)->where('user_id', $userId)->exists()) {
                    $this->warn("العميل {$userId} يملك بيانات في {$table} — لن يُحذف.");
                    continue 2;
                }
            }

            return (int) $userId;
        }

        return null;
    }

    private function idsWhereIn(string $table, string $column, array $values): array
    {
        if (!$values || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)->whereIn($column, $values)->pluck('id')->all();
    }

    private function add(string $table, array $ids, string $note): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $this->plan[] = ['table' => $table, 'ids' => array_values(array_filter($ids)), 'note' => $note];
    }
}
