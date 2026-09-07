<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;


class AccountTransfer extends Finance
{
    // عزل الفروع: المستند يخص فرع منشئه، والسجلات القديمة للفرع الرئيسي.
    use AssignsBranch, BranchScoped;

    protected $table = "fn_account_transfers";

    protected $fillable = [
        'branch_id','debit_account_id', 'credit_account_id','year_id', 'constraint_id', 'cost_center_id', 'value', 'date', 'statement', 'is_confirm'];

    public function files()
    {
        return $this->hasMany(AccountTransferFile::class, 'account_transfer_id', 'id');
    }

    public function debitAccount()
    {
        return $this->belongsTo(Account::class, 'debit_account_id', 'id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(Account::class, 'credit_account_id', 'id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
