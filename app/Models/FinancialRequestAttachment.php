<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRequestAttachment extends Model
{
    protected $guarded = [];

    public function financialRequest()
    {
        return $this->belongsTo(FinancialRequest::class);
    }
}
