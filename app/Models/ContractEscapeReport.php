<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractEscapeReport extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['escape_date' => 'date', 'attachments' => 'array'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function reporter()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'reported_by');
    }
}
