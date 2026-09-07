<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
class ProcurementDocument extends Finance
{
    use AssignsBranch;
    protected $table = 'fn_procurement_documents';
    protected $guarded = [];
    public const STATUSES = ['draft' => 'مسودة', 'approved' => 'معتمد', 'converted' => 'تم التحويل لفاتورة', 'posted' => 'مرحل', 'cancelled' => 'ملغي'];
    public function lines()
    {
        return $this->hasMany(ProcurementLine::class, 'document_id');
    }
    public function payments()
    {
        return $this->hasMany(ProcurementPayment::class, 'document_id');
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function constraint()
    {
        return $this->belongsTo(Constraint::class);
    }
    public function invoice()
    {
        return $this->hasOne(self::class, 'source_id');
    }
    public function getNumberAttribute()
    {
        return $this->exists ? ($this->kind === 'request' ? 'PR-' : 'PI-') . str_pad($this->id, 6, '0', STR_PAD_LEFT) : '';
    }
    public function getBalanceAttribute()
    {
        return round((float) $this->total - (float) $this->payments->sum('amount'), 2);
    }
}
