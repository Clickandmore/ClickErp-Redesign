<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class ComplaintAttachment extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $guarded = [];

    protected $casts = [
        'size' => 'integer',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(Admin::class, 'added_by');
    }
}
