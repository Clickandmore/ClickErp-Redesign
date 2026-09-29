<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Provider references are globally unique; callers authorize by the owning contract. */
class MoyasarContractPayment extends Model
{
    protected $guarded = [];
    protected $casts = ['amount'=>'decimal:2','refunded_amount'=>'decimal:2','paid_at'=>'datetime'];
}
