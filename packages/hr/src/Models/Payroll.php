<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'hr_payrolls';

    protected $fillable = [
        'month_year',
        'month_days',
        'workers_count',
        'total_gross_salary',
        'total_deductions',
        'total_net_salary',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(PayrollItem::class, 'payroll_id');
    }
}
