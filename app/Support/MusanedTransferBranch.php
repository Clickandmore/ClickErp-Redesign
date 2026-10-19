<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class MusanedTransferBranch
{
    /** Resolve all document branches before falling back to the creating employee. */
    public static function resolve(iterable $invoices, ?int $employeeBranchId): ?int
    {
        $branches = [];
        foreach ($invoices as $invoice) {
            foreach ([$invoice->contract?->branch_id, $invoice->ingazDelegation?->branch_id, $invoice->branch_id] as $branchId) {
                if ($branchId) {
                    $branches[(int) $branchId] = true;
                }
            }
        }

        if (count($branches) > 1) {
            throw ValidationException::withMessages([
                'items' => 'لا يمكن جمع عقود أو فواتير من فروع مختلفة في تحويل مساند واحد. أنشئ تحويلًا مستقلًا لكل فرع، وتأكد من تطابق فرع الفاتورة مع عقدها.',
            ]);
        }

        return $branches ? (int) array_key_first($branches) : $employeeBranchId;
    }
}
