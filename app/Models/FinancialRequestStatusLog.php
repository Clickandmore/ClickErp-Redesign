<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class FinancialRequestStatusLog extends Model
{
    protected $guarded = [];

    public function financialRequest()
    {
        return $this->belongsTo(FinancialRequest::class);
    }

    public function performer()
    {
        return $this->belongsTo(Admin::class, 'performed_by_id');
    }
}
