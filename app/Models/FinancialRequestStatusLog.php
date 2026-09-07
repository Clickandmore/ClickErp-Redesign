<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class FinancialRequestStatusLog extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
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
