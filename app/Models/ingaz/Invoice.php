<?php

namespace App\Models\ingaz;

use App\Models\Concerns\AssignsBranch;
use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\MusanedTransferItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\Sale;

class Invoice extends Model
{
    use AssignsBranch, HasFactory;

    protected $guarded = [];
    protected $table = 'invoices';
    protected $casts = [
        'is_paid' => 'boolean',
        'send_to_zatca' => 'boolean',
        'paid_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin();
    }

    public function contract(){
        return $this->belongsTo(Contract::class);
    }

    public function ingazDelegation()
    {
        return $this->belongsTo(IngazDelegation::class, 'ingaz_delegation_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
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

    public function ingazDelegationCostConstraint()
    {
        return $this->belongsTo(Constraint::class, 'ingaz_delegation_cost_constraint_id');
    }

    public function financeSale()
    {
        return $this->belongsTo(Sale::class, 'finance_sale_id');
    }
}
