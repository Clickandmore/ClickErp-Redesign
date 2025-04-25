<?php

namespace App\Models\printSettingDetails;

use App\Models\printSetting\PrintSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;

class PrintSettingDetail extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'print_setting_details';

    public $translatedAttributes = ['title','title_value','print_details_id','locale'];
    protected $fillable = ['print_setting_id','type'];


    protected $translationForeignKey = 'print_details_id';

    public $translationModel = PrintSettingDetailTranslation::class;

    public function printSettings()
    {
        return $this->belongsTo(PrintSetting::class,'print_setting_id');
    }
}
