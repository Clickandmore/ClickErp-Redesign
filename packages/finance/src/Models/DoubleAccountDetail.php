<?php

namespace Nami\Finance\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoubleAccountDetail extends Finance
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $table = 'fn_double_account_details';

    protected $fillable = [
        'account_id',
        'double_account_id'
    ];

    public function doubleAccount() :BelongsTo
    {
        return $this->belongsTo(DoubleAccount::class, 'double_account_id', 'id');
    }

    public function account() :BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
