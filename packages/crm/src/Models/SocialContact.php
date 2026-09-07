<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialContact extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;
    protected $table = 'crm_social_contacts';
    protected $fillable = ['title', 'is_shown'];
    public $translatable = ['title'];
}
