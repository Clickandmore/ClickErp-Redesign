<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepreciationPrinciple extends Model
{
    use HasFactory;
    protected $table = "fn_depreciation_principles";
    protected $fillable =['principle_id','account_id','constraint_id','created_by_id'];

    public function principle()
    {
        return $this->belongsTo(Principle::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class);
    }


    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }

}
