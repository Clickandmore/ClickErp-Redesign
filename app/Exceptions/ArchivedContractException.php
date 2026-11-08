<?php

namespace App\Exceptions;

use RuntimeException;

/** محاولة إنشاء مستند مالي لعقد مؤرشف من شاشات الإدارة. */
class ArchivedContractException extends RuntimeException
{
    public function __construct(public readonly string $contractType, public readonly int $contractId)
    {
        $label = $contractType === 'rental' ? 'عقد الإيجار' : 'عقد الاستقدام';

        parent::__construct("{$label} #{$contractId} مؤرشف، فلا يمكن إنشاء مستند مالي جديد عليه. استرجعه من الأرشيف أولاً.");
    }

    public function render($request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'archived_contract_id' => $this->contractId,
            ], 409);
        }

        return redirect()->back()->withInput()->with('error', $this->getMessage());
    }
}
