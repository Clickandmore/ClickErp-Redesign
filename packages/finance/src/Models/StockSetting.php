<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockSetting extends Finance
{
    use HasFactory;
    protected $table = 'fn_stock_settings';
    protected $guarded = [];
}
