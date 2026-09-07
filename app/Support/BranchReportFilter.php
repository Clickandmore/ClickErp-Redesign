<?php

namespace App\Support;

/**
 * شرط الفرع للاستعلامات التي لا تمرّ بـ Eloquent.
 *
 * الـ global scope (App\Models\Scopes\BranchScope) يعمل على موديلات Eloquent فقط،
 * ولا يرى `DB::table(...)` ولا SQL النصي — وعليهما تُبنى تقارير المالية كلها
 * (ميزان المراجعة، كشف الحساب، كشف البنك، الموازنة). هذه الفئة تعطي التقارير
 * نفس دلالة BranchScope بالضبط:
 *
 *   - لا فرع نشط (الموديول معطّل، إدارة عامة، branches_view_all، artisan) ⇒ بلا شرط.
 *   - موظف الفرع الرئيسي ⇒ فرعه + السجلات القديمة التي branch_id فيها NULL.
 *   - موظف فرع آخر ⇒ فرعه وحده.
 *
 * أي انحراف عن هذه الدلالة يجعل الشاشة والتقرير يعطيان رقمين مختلفين لنفس البيانات.
 */
class BranchReportFilter
{
    /**
     * تطبيق شرط الفرع على Query Builder.
     *
     *   DB::table('fn_constraint_details')
     *       ->tap(fn ($q) => BranchReportFilter::apply($q, 'fn_constraint_details'))
     *
     * @param  string  $table  اسم الجدول أو الاسم المستعار كما يظهر في الاستعلام.
     */
    public static function apply($query, string $table, string $column = 'branch_id')
    {
        $branchId = BranchQueryContext::activeBranchId();

        if ($branchId === null) {
            return $query;
        }

        $qualified = $table . '.' . $column;

        if (BranchQueryContext::activeBranchIsMain()) {
            return $query->where(function ($inner) use ($qualified, $branchId) {
                $inner->where($qualified, $branchId)->orWhereNull($qualified);
            });
        }

        return $query->where($qualified, $branchId);
    }

    /**
     * شرط SQL نصي مع bindings، للاستعلامات التي تُكتب يدوياً (CTE وغيرها).
     *
     * يُرجع شرطاً محايداً `1 = 1` عند غياب العزل بدل سلسلة فارغة، حتى يبقى
     * المستدعي قادراً على وضعه بعد `AND` دون تفريع، ودون أن يتغيّر عدد الـ bindings
     * حسب الحالة — وهو المصدر الأول لانزياح القيم في الاستعلامات اليدوية.
     *
     * @return array{0: string, 1: array<int, int>}
     */
    public static function sqlCondition(string $alias, string $column = 'branch_id'): array
    {
        $branchId = BranchQueryContext::activeBranchId();

        if ($branchId === null) {
            return ['1 = 1', []];
        }

        $qualified = $alias . '.' . $column;

        return BranchQueryContext::activeBranchIsMain()
            ? ["({$qualified} = ? OR {$qualified} IS NULL)", [$branchId]]
            : ["{$qualified} = ?", [$branchId]];
    }

    /** هل يوجد عزل فرع نشط الآن؟ للاستعمال في التفريعات الصريحة. */
    public static function active(): bool
    {
        return BranchQueryContext::activeBranchId() !== null;
    }
}
