<?php

namespace App\Models\ingaz;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'invoices';

    public function contract(){
        return $this->belongsTo(Contract::class);
    }
}
