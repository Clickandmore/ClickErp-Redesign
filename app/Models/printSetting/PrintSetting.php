<?php

namespace App\Models\printSetting;

use App\Models\printSettingDetails\PrintSettingDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;

class PrintSetting extends Model implements TranslatableContract
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable;

    protected $table = 'print_setting';

    public $translatedAttributes = ['first_title','second_title'];
    protected $fillable = ['logo'];


    public $translationModel = PrintSettingTranslation::class;

    public function printDetails()
    {
        return $this->hasMany(PrintSettingDetail::class,'print_setting_id');
    }
}
