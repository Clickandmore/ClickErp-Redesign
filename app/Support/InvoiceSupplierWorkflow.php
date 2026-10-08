<?php

namespace App\Support;

use Nami\Finance\Models\ConfirmSetting;

class InvoiceSupplierWorkflow
{
    public const SETTING_KEY = 'invoice_supplier_workflow';
    public const LEGACY = 'legacy';
    public const UPDATED = 'updated';

    public static function enabled(): bool
    {
        return ConfirmSetting::query()->where('title', self::SETTING_KEY)
            ->latest('updated_at')->value('value') === self::UPDATED;
    }
}
