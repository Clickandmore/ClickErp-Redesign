<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'complaint_comments';

    protected $fillable = [
        'comment',
        'complaint_id',
        'added_by',
        'attachments'
    ];

    // Added By
    public function admin(){
        return $this->belongsTo(Admin::class, 'added_by');
    }

    public function complaint(){
        return $this->belongsTo(Complaint::class);
    }
}
