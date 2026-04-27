<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrinciplePerished extends Finance
{
    protected $table = 'fn_principle_perished';
    protected $fillable = [
        'junk_value',
        'perished_value',
        'perished_account_id',
        'perished_end_date',
        'period',
        'account_type',
        'expense_account_id',
        'principle_id'
    ];

    public function perished_account()
    {
        return $this->belongsTo(Account::class, 'perished_account_id');
    }

    public function expense_account()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }
}
