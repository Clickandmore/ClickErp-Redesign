<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class ComplaintComment extends Model
{
    use AssignsBranch, HasFactory, BranchScoped;

    protected $guarded = [];

    protected $table = 'complaint_comments';

    protected $fillable = [
        'comment',
        'complaint_id',
        'branch_id',
        'added_by',
        'attachments'
    ];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    // Added By
    public function admin(){
        return $this->belongsTo(Admin::class, 'added_by');
    }

    public function complaint(){
        return $this->belongsTo(Complaint::class);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromComplaint($this->complaint_id)
            ?? $this->resolveBranchIdFromAdmin($this->added_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
