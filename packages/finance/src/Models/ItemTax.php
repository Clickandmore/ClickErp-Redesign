<?php

namespace Nami\Finance\Models;


class ItemTax extends Finance
{
    protected $table = 'fn_item_taxs';

    // Fillable fields for mass assignment
    protected $fillable = [
        'item_id',
        'tax_rate',
        'tax_amount',
        'tax_setting_id',
        'tax_account_id',
    ];

    // Relationships

    // An ItemTax belongs to an item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // An ItemTax belongs to a tax setting
    public function taxSetting()
    {
        return $this->belongsTo(TaxSetting::class, 'tax_setting_id');
    }

    // An ItemTax belongs to a tax account
    public function taxAccount()
    {
        return $this->belongsTo(Account::class, 'tax_account_id');
    }
}

