<?php

namespace Nami\Crm\Models;

use App\Models\Nami\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowClient extends Model
{
    use HasFactory;
    protected $table = 'crm_follow_client';
    protected $fillable = ['client_id','admin_id', 'follow_clients_category_id', 'date', 'content', 'notes', 'result'];

    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }

    public function user()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }
    public function category()
    {
        return $this->belongsTo(FollowClientCategory::class,'follow_clients_category_id');
    }
}
