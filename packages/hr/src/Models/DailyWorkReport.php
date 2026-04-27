<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Model;
class DailyWorkReport extends Model
{
    // use HasFactory;
    protected $table = 'hr_daily_work_reports';
    protected $fillable = ['employee_id','title','description','date','file'];

    public function employee(){
        return $this->belongsTo(Employee::class,'employee_id','id');
    }

}
