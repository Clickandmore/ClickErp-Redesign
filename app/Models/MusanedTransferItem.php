<?php

namespace App\Models;

use App\Models\ingaz\Invoice;
use App\Models\IngazDelegation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\CostCenter;

class MusanedTransferItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'customer_paid_amount' => 'decimal:2',
        'transferred_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'expense_tax_amount' => 'decimal:2',
        'commission_net_amount' => 'decimal:2',
    ];

    public function transfer()
    {
        return $this->belongsTo(MusanedTransfer::class, 'musaned_transfer_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function ingazDelegation()
    {
        return $this->belongsTo(IngazDelegation::class, 'ingaz_delegation_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function sourceAccount()
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }
}
