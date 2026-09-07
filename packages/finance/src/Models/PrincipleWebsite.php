<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrincipleWebsite extends Finance
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $table = 'fn_principle_websites';
    protected $fillable = ['title','created_by_id'];


    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }
    public function principles()
    {
        return $this->hasMany(Principle::class,'principle_website_id');
    }
}
