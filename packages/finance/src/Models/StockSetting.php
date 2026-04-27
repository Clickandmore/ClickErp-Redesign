<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockSetting extends Model
{
    use HasFactory;
    protected $table = 'fn_stock_settings';
    protected $guarded = [];
}
