<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ZatcaInvoice extends Finance
{
    use HasFactory;
    protected $guarded = [];

    public function sale()
    {
        return $this->belongsTo(Sale::class,'sale_id');
    }
}
