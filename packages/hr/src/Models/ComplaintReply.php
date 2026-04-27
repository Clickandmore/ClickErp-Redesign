<?php

namespace Nami\Hr\Models;

use App\Models\Nami\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintReply extends Model
{
    use HasFactory;
    protected $table = 'hr_complaint_replies';
    protected $fillable = ['complaint_id','date','text','admin_id'];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class,'complaint_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }
}
