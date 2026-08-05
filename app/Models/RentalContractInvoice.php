<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\Package;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;

class RentalContractInvoice extends Model
{
    use AssignsBranch, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'branch_id' => 'integer',
        'send_to_zatca' => 'boolean',
        'finance_sale_id' => 'integer',
    ];

    public function rentalContract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function invoiceConstraint()
    {
        return $this->belongsTo(Constraint::class, 'invoice_constraint_id');
    }

    public function receiptConstraint()
    {
        return $this->belongsTo(Constraint::class, 'receipt_constraint_id');
    }

    public function receiptBond()
    {
        return $this->belongsTo(Bond::class, 'receipt_bond_id');
    }

    public function receiptAccount()
    {
        return $this->belongsTo(Account::class, 'receipt_account_id');
    }

    public function financeSale()
    {
        return $this->belongsTo(\Nami\Finance\Models\Sale::class, 'finance_sale_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromRentalContract($this->rental_contract_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
