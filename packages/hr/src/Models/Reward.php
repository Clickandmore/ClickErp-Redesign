<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;
    protected $table = 'hr_rewards';
    protected $fillable = ['title','type','value','status'];

    public function employeeReward()
    {
        return $this->belongsToOne(EmployeeReward::class);
    }
}
