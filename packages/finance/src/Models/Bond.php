<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bond extends Finance
{
    use HasFactory;

    protected $table ='fn_bonds';
    protected $fillable = ['type','payment_type','bond_book_num','debit_account_id',
        'credit_account_id','value','date','statement','file','transfer_number','code',
        'transfer_file','is_confirm','constraint_id','created_by_id','year_id','cost_center_id',
        'is_tax','net','account_text','general_type',
        'legacy_source','legacy_source_file','legacy_source_sheet','legacy_source_row','legacy_sequence',
        'legacy_hijri_date','legacy_total','legacy_currency','legacy_financial_movement',
        'legacy_financial_movement_currency','legacy_client_number','legacy_order_number',
        'recruitment_contract_id','rental_contract_id','legacy_source_payload'
    ];

    protected $casts = [
        'legacy_source_payload' => 'array',
        'legacy_total' => 'decimal:4',
        'legacy_financial_movement' => 'decimal:4',
        'legacy_client_number' => 'integer',
        'legacy_order_number' => 'integer',
    ];

    public function debitAccount(){
        return $this->belongsTo(Account::class,"debit_account_id","id");
    }

    public function creditAccount(){
        return $this->belongsTo(Account::class,"credit_account_id","id");
    }

    public function check(){
        return $this->hasOne(BondChecks::class,"bond_id","id");
    }

    public function costCenter(){
        return $this->belongsTo(CostCenter::class,"cost_center_id");
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class,'constraint_id');
    }

    public function bondTax()
    {
        return $this->hasOne(BondTax::class,'bond_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function recruitmentContract()
    {
        return $this->belongsTo(Contract::class, 'recruitment_contract_id');
    }

    public function rentalContract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

}
