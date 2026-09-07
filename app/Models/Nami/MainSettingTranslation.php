<?php

namespace App\Models\Nami;

use Illuminate\Database\Eloquent\Model;


class MainSettingTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */


    /**
     * @var array
     */
    protected $table = 'main_setting_translations';
    protected $fillable = ['sidebar_text', 'copyright_text','company_name'];

}
