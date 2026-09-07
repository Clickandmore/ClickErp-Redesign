<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryPoliceTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['name', 'text_file_1', 'text_file_2'];
    protected $table = 'country_police_translations';

}
