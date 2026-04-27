<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCategory extends Model
{
    use HasFactory;
    protected $table = 'crm_client_category';
    protected $fillable = ['client_id', 'category_id'];

    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }
}
