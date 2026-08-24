<?php

namespace Nami\Finance\Models;
use App\Models\auth\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FnYear extends Finance
{
    protected $table ='fn_years';
    protected $fillable =['title','from_date','to_date','status', 'created_by_id'];

    public function constraints()
    {
        return $this->hasMany(Constraint::class, 'year_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function hasChildrens()
    {
        return  $this->constraints()->count() > 0;
    }
}
