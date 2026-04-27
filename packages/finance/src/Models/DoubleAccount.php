<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class DoubleAccount extends Finance
{
    protected $table = 'fn_double_accounts';

    protected $fillable = [
        'name',
    ];

    public function details() :HasMany
    {
        return $this->hasMany(DoubleAccountDetail::class, 'double_account_id', 'id');
    }

    public function accounts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'fn_double_account_details', 'double_account_id', 'account_id');
    }

}
