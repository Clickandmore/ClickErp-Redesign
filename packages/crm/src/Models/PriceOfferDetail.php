<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Calculation\Web\Service;

class PriceOfferDetail extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;
    protected $table = 'crm_price_offers_details';
    protected $fillable = ['price_offer_id', 'service_id', 'amount', 'price', 'discount', 'description'];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function price_offer()
    {
        return $this->belongsTo(PriceOffer::class, 'price_offer_id');
    }
}
