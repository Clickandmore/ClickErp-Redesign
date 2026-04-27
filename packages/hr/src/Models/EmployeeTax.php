<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTax extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'hr_employee_tax';


    // public function employees()
    // {
    //     return $this->belongsToMany(Employee::class, 'hr_employee_tax', 'tax_id', 'employee_id');
    // }
    // public function tax()
    // {
    //     return $this->belongsTo(Tax::class, 'tax_id');
    // }

}
