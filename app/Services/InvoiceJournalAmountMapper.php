<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/** Match existing journal rows to invoice components without changing their accounts. */
class InvoiceJournalAmountMapper
{
    public function map(array $rows, array $before, array $after): array
    {
        $keys = array_keys(array_filter($before, fn ($amount, $key) => $amount !== 0 || $after[$key] !== 0, ARRAY_FILTER_USE_BOTH));
        $full = (1 << count($keys)) - 1;
        $subsets = [];
        for ($mask = 1; $mask <= $full; $mask++) {
            $old = $new = 0;
            foreach ($keys as $bit => $key) {
                if ($mask & (1 << $bit)) {
                    $old += $before[$key];
                    $new += $after[$key];
                }
            }
            $subsets[$mask] = [$old, $new];
        }
        $result = [];
        foreach (['debtor_value', 'creditor_value'] as $side) {
            $lines = array_values(array_filter($rows, fn ($row) => (int) round((float) $row[$side] * 100) > 0));
            if (count($lines) > count($keys)) {
                $this->ambiguous();
            }
            $solutions = [];
            $search = function ($index, $used, $values) use (&$search, &$solutions, $lines, $side, $subsets, $full) {
                if (count($solutions) > 1) return;
                if ($index === count($lines)) {
                    if ($used === $full) $solutions[json_encode($values)] = $values;
                    return;
                }
                $amount = (int) round((float) $lines[$index][$side] * 100);
                foreach ($subsets as $mask => [$old, $new]) {
                    if (($mask & $used) === 0 && $old === $amount) {
                        $search($index + 1, $used | $mask, $values + [$lines[$index]['id'] => $new / 100]);
                    }
                }
            };
            $search(0, 0, []);
            if (count($solutions) !== 1) $this->ambiguous();
            foreach (reset($solutions) as $id => $amount) $result[$id][$side] = $amount;
        }
        return $result;
    }

    private function ambiguous(): void
    {
        throw ValidationException::withMessages(['invoice' => 'تعذر مطابقة بنود الفاتورة مع أسطر القيد بشكل وحيد. يلزم مراجعة توزيع القيد قبل التصحيح؛ لم يتم تغيير أي بيانات.']);
    }
}
