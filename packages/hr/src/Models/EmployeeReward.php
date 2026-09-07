<?php

namespace Nami\Hr\Models;

use Nami\Hr\Models\Reward;
use Nami\Hr\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeReward extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'hr_reward_employee';
    protected $fillable = ['employee_id','reward_id','salary','exchange_timing','date','value','net_value'];

    protected $casts =[
        'exchange_timing' => \Nami\Hr\Enums\ExchangeTimingEnum::class,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class,'reward_id');
    }
}
