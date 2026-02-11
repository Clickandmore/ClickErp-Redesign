<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractNote extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $fillable = ['contract_id', 'admin_id', 'note'];

    public function contract(){
        return $this->belongsTo(Contract::class);
    }
    public function admin(){
        return $this->belongsTo(Admin::class);
    }
}
