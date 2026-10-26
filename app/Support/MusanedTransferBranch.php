<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class MusanedTransferBranch
{
    /** Return the branch carried by a source document, without employee fallback. */
    public static function branchForInvoice($invoice): ?int
    {
        $branches = [];
        foreach ([$invoice->contract?->branch_id, $invoice->ingazDelegation?->branch_id, $invoice->branch_id] as $branchId) {
            if ($branchId) $branches[(int) $branchId] = true;
        }
        if (count($branches) > 1) {
            throw ValidationException::withMessages([
                'items' => 'فرع العقد لا يطابق فرع الفاتورة أو التفويض المرتبط به.',
            ]);
        }
        return $branches ? (int) array_key_first($branches) : null;
    }

    /** @return array<int, int> */
    public static function branchIds(iterable $invoices): array
    {
        $branches = [];
        foreach ($invoices as $invoice) {
            $branchId = self::branchForInvoice($invoice);
            if ($branchId) $branches[$branchId] = $branchId;
        }
        return array_values($branches);
    }

    /** Resolve all document branches before falling back to the creating employee. */
    public static function resolve(iterable $invoices, ?int $employeeBranchId): ?int
    {
        $branches = [];
        foreach ($invoices as $invoice) {
            $branchId = self::branchForInvoice($invoice);
            if ($branchId) $branches[$branchId] = true;
        }
        if (count($branches) > 1) {
            throw ValidationException::withMessages([
                'items' => 'لا يمكن تجميع فواتير من فروع مختلفة في هذا المسار.',
            ]);
        }
        return $branches ? (int) array_key_first($branches) : $employeeBranchId;
    }
}
