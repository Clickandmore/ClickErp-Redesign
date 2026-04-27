<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;
    protected $table = 'hr_tax';
    protected $fillable = ['title','value','status'];

    public function employee() {
        return $this->belongsToMany(Employee::class, 'hr_employee_tax', 'tax_id', 'employee_id');
    }
}
