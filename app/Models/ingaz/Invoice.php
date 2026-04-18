<?php

namespace App\Models\ingaz;

use App\Models\Contract;
use App\Models\MusanedTransferItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Constraint;

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

    public function arrivalRevenueConstraint()
    {
        return $this->belongsTo(Constraint::class, 'arrival_revenue_constraint_id');
    }

    public function externalOfficeCostConstraint()
    {
        return $this->belongsTo(Constraint::class, 'external_office_cost_constraint_id');
    }
}
