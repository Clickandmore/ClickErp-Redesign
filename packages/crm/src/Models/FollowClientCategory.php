<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FollowClientCategory extends Model
{
    use HasFactory;
    protected $table = 'crm_follow_clients_categories';
    protected $fillable = ['title', 'is_shown'];
}
