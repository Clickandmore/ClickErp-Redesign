<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;
    protected $table = 'crm_categories';
    protected $fillable = ['name', 'is_shown'];


}
