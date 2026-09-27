<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يحدد ما إذا كان قيد محاسبي (fn_constraints) قيداً آلياً مرتبطاً بمستند تشغيلي
 * (فاتورة، تحويل مساند، استرداد، نقل خدمات، إيواء، ترحيل، سند...).
 *
 * القيود الآلية تُعكس بقيد مضاد ولا تُحذف، لأن حذفها يترك المستند الأصلي بلا أثر
 * محاسبي ويكسر التقارير. كل فحص يمر عبر Schema::hasTable / hasColumn حتى يظل
 * الصنف آمناً على قواعد بيانات لم تُطبَّق عليها كل الهجرات بعد.
 */
class ConstraintReferenceGuard
{
    /**
     * الأعمدة التي تشير إلى fn_constraints.id مع الوصف العربي لكل جدول.
     *
     * @var array<string, array{label: string, columns: string[]}>
     */
    private const REFERENCES = [
        'contract_return_settlement_lines' => [
            'label' => 'بند استرداد بعد الوصول',
            'columns' => ['constraint_id', 'reversal_constraint_id'],
        ],
        'shelter_agent_cost_recoveries' => [
            'label' => 'استرداد تكلفة الوكيل',
            'columns' => ['constraint_id', 'reversal_constraint_id'],
        ],
        'invoices' => [
            'label' => 'فاتورة',
            'columns' => [
                'invoice_constraint_id',
                'constraint_id',
                'arrival_revenue_constraint_id',
                'external_office_cost_constraint_id',
                'external_office_cost_reversal_constraint_id',
                'ingaz_delegation_cost_constraint_id',
            ],
        ],
        'recruitment_invoice_credit_notes' => [
            'label' => 'إشعار دائن',
            'columns' => ['constraint_id'],
        ],
        'recruitment_contract_refunds' => [
            'label' => 'استرداد عقد استقدام',
            'columns' => ['refund_constraint_id'],
        ],
        'musaned_transfers' => [
            'label' => 'تحويل مساند',
            'columns' => ['settlement_constraint_id', 'bank_transfer_constraint_id'],
        ],
        'sponsorship_transfers' => [
            'label' => 'نقل خدمات',
            'columns' => [
                'trial_start_constraint_id',
                'completion_constraint_id',
                'failure_constraint_id',
                'receipt_constraint_id',
                'refund_constraint_id',
            ],
        ],
        'shelter_living_expenses' => [
            'label' => 'مصروف إعاشة',
            'columns' => ['constraint_id', 'reversed_by_constraint_id'],
        ],
        'shelter_cost_entries' => [
            'label' => 'دفتر الإيواء',
            'columns' => ['constraint_id', 'supplier_constraint_id'],
        ],
        'deportation_procedures' => [
            'label' => 'ترحيل عاملة',
            'columns' => ['constraint_id'],
        ],
        'contract_return_requests' => [
            'label' => 'طلب إرجاع داخل الضمان',
            'columns' => ['shelter_constraint_id', 'return_constraint_id'],
        ],
        'fn_bonds' => [
            'label' => 'سند',
            'columns' => ['constraint_id'],
        ],
    ];

    /**
     * الأعمدة الموجودة فعلاً في قاعدة البيانات الحالية (تُحسب مرة واحدة لكل طلب).
     *
     * @var array<string, array{label: string, columns: string[]}>|null
     */
    private static ?array $availableReferences = null;

    /**
     * @return array<int, array{table: string, column: string, id: int, label: string}>
     */
    public static function referencesFor(int $constraintId): array
    {
        if ($constraintId <= 0) {
            return [];
        }

        return self::referencesForMany([$constraintId])[$constraintId] ?? [];
    }

    /**
     * نسخة دفعية: تُرجع المراجع مفهرسة برقم القيد (القيود غير المرتبطة لا تظهر في النتيجة).
     *
     * @param  int[]  $constraintIds
     * @return array<int, array<int, array{table: string, column: string, id: int, label: string}>>
     */
    public static function referencesForMany(array $constraintIds): array
    {
        $constraintIds = array_values(array_unique(array_filter(array_map('intval', $constraintIds), fn ($id) => $id > 0)));
        if (empty($constraintIds)) {
            return [];
        }

        $available = self::availableReferences();
        if (empty($available)) {
            return [];
        }

        $union = null;
        $placeholders = implode(',', array_fill(0, count($constraintIds), '?'));

        foreach ($available as $table => $definition) {
            foreach ($definition['columns'] as $column) {
                $select = DB::table($table)
                    ->selectRaw(
                        '? as ref_table, ? as ref_column, `' . $table . '`.`id` as ref_id, `' . $table . '`.`' . $column . '` as constraint_id',
                        [$table, $column]
                    )
                    ->whereRaw('`' . $table . '`.`' . $column . '` in (' . $placeholders . ')', $constraintIds);

                $union = $union ? $union->unionAll($select) : $select;
            }
        }

        $result = [];
        foreach ($union->get() as $row) {
            $constraintId = (int) $row->constraint_id;
            $result[$constraintId][] = [
                'table' => (string) $row->ref_table,
                'column' => (string) $row->ref_column,
                'id' => (int) $row->ref_id,
                'label' => $available[$row->ref_table]['label'],
            ];
        }

        return $result;
    }

    public static function isReferenced(int $constraintId): bool
    {
        return ! empty(self::referencesFor($constraintId));
    }

    /**
     * جملة عربية مثل:
     * «هذا قيد آلي مرتبط بـ فاتورة #12، تحويل مساند #3. القيود الآلية تُعكس ولا تُحذف.»
     */
    public static function describe(int $constraintId): string
    {
        $references = self::referencesFor($constraintId);
        if (empty($references)) {
            return 'هذا القيد غير مرتبط بأي مستند.';
        }

        $mentions = [];
        foreach ($references as $reference) {
            $mentions[$reference['label'] . ' #' . $reference['id']] = true;
        }

        return 'هذا قيد آلي مرتبط بـ ' . implode('، ', array_keys($mentions)) . '. القيود الآلية تُعكس ولا تُحذف.';
    }

    /**
     * يُستخدم في الاختبارات وبعد تطبيق هجرات جديدة داخل نفس العملية.
     */
    public static function flushSchemaCache(): void
    {
        self::$availableReferences = null;
    }

    /**
     * @return array<string, array{label: string, columns: string[]}>
     */
    private static function availableReferences(): array
    {
        if (self::$availableReferences !== null) {
            return self::$availableReferences;
        }

        $available = [];
        foreach (self::REFERENCES as $table => $definition) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                $definition['columns'],
                fn (string $column) => Schema::hasColumn($table, $column)
            ));

            if (! empty($columns)) {
                $available[$table] = [
                    'label' => $definition['label'],
                    'columns' => $columns,
                ];
            }
        }

        return self::$availableReferences = $available;
    }
}
