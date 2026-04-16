<?php

namespace App\Models\ingaz;

use App\Models\Contract;
use App\Models\MusanedTransferItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'invoices';
    protected $casts = [
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function contract(){
        return $this->belongsTo(Contract::class);
    }

    public function musanedTransferItem()
    {
        return $this->hasOne(MusanedTransferItem::class, 'invoice_id');
    }
}
