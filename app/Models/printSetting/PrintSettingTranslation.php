<?php

namespace App\Models\printSetting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintSettingTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    // use HasFactory;
    public $timestamps = false;

    protected $table = 'print_setting_translations';

    protected $fillable = ['first_title','second_title'];
}
