<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoliceSettingTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    public $timestamps = false;
    protected $fillable =['title_1', 'title_2', 'description_1', 'description_2'];
    protected $table = 'police_setting_translations';
}
