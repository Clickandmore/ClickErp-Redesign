<?php

namespace Nami\Finance\Models;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BondChecks extends Finance
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'fn_bond_checks';


    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }


    public function bond()
    {
        return $this->belongsTo(Bond::class,'bond_id');
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class,'constraint_id');
    }


}
