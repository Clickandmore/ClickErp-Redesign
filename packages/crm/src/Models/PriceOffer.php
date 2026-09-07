<?php

namespace Nami\Crm\Models;

use App\Enums\crm\PriceOfferStatus;
use App\Model\User;
use App\Models\Nami\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceOffer extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'crm_price_offers';

    protected $casts = [
        'status' => PriceOfferStatus::class
    ];
    protected $fillable = ['client_id','admin_id', 'file','date', 'total', 'status','terms','notes','desc'];

    public function client()
    {
        return $this->belongsTo(Client::class ,'client_id');
    }

    public function user()
    {
        return $this->belongsTo(Admin::class ,'admin_id');
    }

    public function details()
    {
        return $this->hasMany(PriceOfferDetail::class ,'price_offer_id');
    }
}
