<?php

namespace App\Models\commissions;

use App\Models\IngazDelegation;
use Illuminate\Database\Eloquent\Model;

class CommissionBatchItem extends Model
{
    protected $table = 'commission_batch_items';

    protected $fillable = [
        'batch_id', 'ingaz_delegation_id', 'source_type', 'source_id',
        'order_id', 'contract_id', 'operation_reference',
        'client_name', 'operation_date', 'service_type',
        'base_amount', 'method', 'rate_or_amount', 'commission_amount',
        'commission_reversal_transaction_id', 'commission_reversed_at', 'commission_reversal_reason',
        'legacy_source', 'legacy_source_file', 'legacy_source_row',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'rate_or_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'operation_date' => 'date',
        'commission_reversed_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(CommissionBatch::class, 'batch_id');
    }

    public function delegation()
    {
        return $this->belongsTo(IngazDelegation::class, 'ingaz_delegation_id');
    }

    public function reversalTransaction()
    {
        return $this->belongsTo(CommissionBalanceTransaction::class, 'commission_reversal_transaction_id');
    }
}
