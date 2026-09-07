<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class PoliceSetting extends Model implements TranslatableContract
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable;

    protected $table = 'police_settings';

    public $translatedAttributes = ['title_1', 'title_2', 'description_1', 'description_2'];
    protected $fillable = ['file_employee_rights', 'file_workers_rights'];
}
