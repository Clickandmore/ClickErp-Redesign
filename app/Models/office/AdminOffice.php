<?php

namespace App\Models\office;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminOffice extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    protected $guarded = [];

    public function admin()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'admin_id');
    }
}
