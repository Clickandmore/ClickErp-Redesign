<?php

namespace App\Services\Finance;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\AccountTypesEnum;

/**
 * تجهيز شجرة حسابات مستقلة لكل فرع.
 *
 * لكل فرع حساب خاص تحت كل مرساة في config/branch_finance.php (صندوق، بنك،
 * عملاء، موردون، إيرادات، مصروفات) بالإضافة إلى مركز تكلفة، فتبقى القوائم
 * المالية المجمّعة صحيحة بينما يقرأ كل فرع أرصدته وحده.
 *
 * العملية idempotent: إعادة التشغيل لا تنشئ حسابات مكررة.
 */
class BranchChartOfAccountsService
{
    public function financeTablesAvailable(): bool
    {
        return Schema::hasTable('fn_accounts')
            && Schema::hasTable('branch_finance_accounts')
            && Schema::hasTable('fn_account_branch')
            && Schema::hasColumn('fn_accounts', 'branch_id');
    }

    /**
     * تجهيز حسابات الفرع ومركز تكلفته.
     *
     * @return array{accounts: array<string, int>, cost_center_id: int|null, created: int}
     */
    public function provision(Branch $branch): array
    {
        if (! $this->financeTablesAvailable()) {
            return ['accounts' => [], 'cost_center_id' => null, 'created' => 0];
        }

        return DB::transaction(function () use ($branch) {
            $created = 0;
            $accounts = [];

            foreach ($this->anchors() as $purpose => $anchor) {
                $existing = $this->existingAccountId($branch, $purpose);

                if ($existing) {
                    $this->ensureBranchAssignment($branch, $existing);
                    $this->normalizeBranchAccountType($existing, $purpose);
                    $accounts[$purpose] = $existing;
                    continue;
                }

                $parent = $this->anchorAccount($anchor['code']);

                if (! $parent) {
                    continue;
                }

                $accountId = $this->createBranchAccount($branch, $parent, $anchor['label'], $purpose);
                $this->linkAccount($branch, $purpose, $accountId);
                $this->ensureBranchAssignment($branch, $accountId);

                $accounts[$purpose] = $accountId;
                $created++;
            }

            $costCenterId = $this->ensureCostCenter($branch);

            $branch->forceFill([
                'cost_center_id' => $costCenterId,
                'finance_provisioned_at' => now(),
            ])->save();

            return ['accounts' => $accounts, 'cost_center_id' => $costCenterId, 'created' => $created];
        });
    }

    /** حسابات الفرع مفهرسة بالغرض: ['cash' => 1234, ...] */
    public function accountsFor(Branch $branch): array
    {
        if (! $this->financeTablesAvailable()) {
            return [];
        }

        return DB::table('branch_finance_accounts')
            ->where('branch_id', $branch->id)
            ->pluck('account_id', 'purpose')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** كل معرفات الحسابات المملوكة لفرع، شاملة الحسابات الفرعية تحتها. */
    public function accountIdsFor(Branch $branch): Collection
    {
        if (! $this->financeTablesAvailable()) {
            return collect();
        }

        $roots = collect($this->accountsFor($branch))->values();

        if ($roots->isEmpty()) {
            return collect();
        }

        $all = collect($roots);
        $frontier = $roots;

        // شجرة الحسابات هنا 5 مستويات كحد أقصى، فالتوسّع المتكرر آمن.
        while ($frontier->isNotEmpty()) {
            $children = DB::table('fn_accounts')
                ->whereIn('parent_id', $frontier->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $frontier = $children->diff($all)->values();
            $all = $all->merge($frontier)->unique()->values();
        }

        return $all;
    }

    /** الفرع المالك لحساب معيّن، أو null للحسابات المشتركة. */
    public function branchIdForAccount(?int $accountId): ?int
    {
        if (! $accountId || ! $this->financeTablesAvailable()) {
            return null;
        }

        // الحساب نفسه، ثم أقرب أب يحمل فرعاً.
        for ($i = 0, $current = $accountId; $current && $i < 10; $i++) {
            $row = DB::table('fn_accounts')->where('id', $current)->first(['branch_id', 'parent_id']);

            if (! $row) {
                return null;
            }

            if (! empty($row->branch_id)) {
                return (int) $row->branch_id;
            }

            $current = $row->parent_id ? (int) $row->parent_id : null;
        }

        return null;
    }

    private function anchors(): array
    {
        return (array) config('branch_finance.anchors', []);
    }

    private function existingAccountId(Branch $branch, string $purpose): ?int
    {
        $accountId = DB::table('branch_finance_accounts')
            ->where('branch_id', $branch->id)
            ->where('purpose', $purpose)
            ->value('account_id');

        if (! $accountId) {
            return null;
        }

        // الحساب قد يكون حُذف من شجرة الحسابات بعد الربط.
        return DB::table('fn_accounts')->where('id', $accountId)->exists() ? (int) $accountId : null;
    }

    private function anchorAccount(string $code): ?object
    {
        return DB::table('fn_accounts')->where('code', \Nami\Finance\Support\AccountCodeAliases::current($code))->first(['id', 'code', 'level', 'type', 'statement_type']);
    }

    private function createBranchAccount(Branch $branch, object $parent, string $label, string $purpose): int
    {
        $name = trim(($branch->name_ar ?: $branch->name_en) . ' — ' . $label);

        $accountId = DB::table('fn_accounts')->insertGetId([
            'branch_id' => $branch->id,
            'name' => $name,
            'code' => $this->nextChildCode($parent),
            'level' => (int) $parent->level + 1,
            'type' => $this->typeForPurpose($purpose, $parent->type),
            'statement_type' => $parent->statement_type,
            'parent_id' => $parent->id,
            'status' => 1,
            'operation_type' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $accountId;
    }

    /**
     * أكواد شجرة الحسابات هرمية رقمية: كود الابن = كود الأب × 100 + ترتيبه،
     * وهو نفس ما يفعله Nami\Finance\Services\AccountService.
     *
     * لكن `fn_accounts.code` فريد على مستوى الشجرة كلها، وترقيم البيانات
     * القديمة غير منتظم، فقد يصطدم max+1 بكود ابن لأب آخر — لذا نتقدّم
     * حتى أول كود غير مستعمل.
     */
    private function nextChildCode(object $parent): string
    {
        $base = \Nami\Finance\Support\ChartSettings::childCodeStart($parent->code, (int) $parent->level) ?? ((int) $parent->code) * 100;

        $maxChildCode = (int) DB::table('fn_accounts')
            ->where('parent_id', $parent->id)
            ->max(DB::raw('CAST(code AS UNSIGNED)'));

        $candidate = $maxChildCode >= $base ? $maxChildCode + 1 : $base + 1;

        while (DB::table('fn_accounts')->where('code', (string) $candidate)->exists()) {
            $candidate++;
        }

        return (string) $candidate;
    }

    private function linkAccount(Branch $branch, string $purpose, int $accountId): void
    {
        DB::table('branch_finance_accounts')->updateOrInsert(
            ['branch_id' => $branch->id, 'purpose' => $purpose],
            ['account_id' => $accountId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function ensureBranchAssignment(Branch $branch, int $accountId): void
    {
        if (! Schema::hasTable('fn_account_branch')) {
            return;
        }

        DB::table('fn_account_branch')->insertOrIgnore([
            'account_id' => $accountId,
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function normalizeBranchAccountType(int $accountId, string $purpose): void
    {
        $targetType = $this->typeForPurpose($purpose);
        if ($targetType === null) {
            return;
        }

        $hasChildren = DB::table('fn_accounts')->where('parent_id', $accountId)->exists();
        if ($hasChildren) {
            return;
        }

        DB::table('fn_accounts')
            ->where('id', $accountId)
            ->update(['type' => $targetType, 'updated_at' => now()]);
    }

    private function typeForPurpose(?string $purpose, mixed $fallback = null): mixed
    {
        return match ($purpose) {
            'cash' => AccountTypesEnum::Box->value,
            'bank' => AccountTypesEnum::Bank->value,
            'clients' => AccountTypesEnum::Client->value,
            'suppliers' => AccountTypesEnum::Supplier->value,
            'revenue', 'expenses' => AccountTypesEnum::NormalAccount->value,
            default => $fallback,
        };
    }

    private function ensureCostCenter(Branch $branch): ?int
    {
        if (! Schema::hasTable('fn_cost_centers')) {
            return null;
        }

        $existing = (int) ($branch->cost_center_id ?? 0);

        if ($existing > 0 && DB::table('fn_cost_centers')->where('id', $existing)->exists()) {
            return $existing;
        }

        $rootConfig = (array) config('branch_finance.cost_center_root', []);
        $rootCode = (string) ($rootConfig['code'] ?? 'BRANCHES');

        $rootId = (int) (DB::table('fn_cost_centers')->where('code', $rootCode)->value('id') ?? 0);

        if ($rootId <= 0) {
            $rootId = (int) DB::table('fn_cost_centers')->insertGetId([
                'title' => (string) ($rootConfig['title'] ?? 'الفروع'),
                'code' => $rootCode,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $code = $rootCode . '-' . ($branch->code ?: $branch->id);

        $costCenterId = (int) (DB::table('fn_cost_centers')->where('code', $code)->value('id') ?? 0);

        if ($costCenterId > 0) {
            return $costCenterId;
        }

        $payload = [
            'title' => $branch->name_ar ?: $branch->name_en,
            'code' => $code,
            'parent_id' => $rootId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('fn_cost_centers', 'branch_id')) {
            $payload['branch_id'] = $branch->id;
        }

        return (int) DB::table('fn_cost_centers')->insertGetId($payload);
    }
}
