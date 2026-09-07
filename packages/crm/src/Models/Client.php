<?php

namespace Nami\Crm\Models;

use App\Models\Nami\Admin;
use App\Models\ingaz\User as ClientUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'crm_clients';
    protected $fillable = ['name', 'phone', 'whatsapp',  'vat_number', 'email', 'notes', 'code', 'social_contact_id', 'city_id', 'area_id', 'status', 'address', 'admin_id', 'client_id'];

    public function social_contact()
    {
        return $this->belongsTo(SocialContact::class,'social_contact_id');
    }

    public function city()
    {
        return $this->belongsTo(Area::class,'city_id');
    }

    public function priceOffers()
    {
        return $this->hasMany(PriceOffer::class,'client_id');
    }

    public function priceOffersDetails()
    {
        return $this->hasManyThrough(
            PriceOfferDetail::class,
            PriceOffer::class,
            'client_id', // Foreign key on PriceOffer table...
            'price_offer_id', // Foreign key on PriceOfferDetail table...
            'id', // Local key on Client table...
            'id' // Local key on PriceOffer table...
        );
    }

    public function area()
    {
        return $this->belongsTo(Area::class,'area_id');
    }

    public function user()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }

    public function client()
    {
        return $this->belongsTo(ClientUser::class, 'client_id');
    }

    public function category() {
        return $this->belongsToMany(Category::class, 'crm_client_category', 'client_id', 'category_id');
    }
    public function categories() {
        return $this->hasMany(ClientCategory::class, 'category_id','id');
    }

    public function hasCategory($categoryId)
    {
        return in_array($categoryId, $this->category->pluck('id')->toArray());
    }
}
