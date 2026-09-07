<?php

namespace App\Models;

use App\Models\ingaz\Maid;
use App\Models\Contract;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaidUnavailableRecord extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    protected $fillable = [
        'maid_id',
        'reason',
        'contract_id',
        'admin_id'
    ];

    /**
     * Get the maid that owns the record
     */
    public function maid()
    {
        return $this->belongsTo(Maid::class);
    }

    /**
     * Get the contract that caused the unavailability
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the admin who made the change
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
