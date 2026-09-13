<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Model;

class SponsorshipTransferMaidReplacement extends Model
{
    protected $guarded = [];

    protected $casts = ['old_maid_made_available' => 'boolean'];

    public function oldMaid() { return $this->belongsTo(Maid::class, 'old_maid_id'); }
    public function newMaid() { return $this->belongsTo(Maid::class, 'new_maid_id'); }
    public function changedBy() { return $this->belongsTo(Admin::class, 'changed_by'); }
}
