<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;
    protected $table = 'hr_complaints';
    protected $fillable = ['employee_id','date','description','directed_to_id','status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public function directed_to()
    {
        return $this->belongsTo(Employee::class,'directed_to_id');
    }

    public function replies()
    {
        return $this->hasMany(ComplaintReply::class);
    }


}
