<?php

namespace App\Models\printSettingDetails;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintSettingDetailTranslation extends Model
{
    // use HasFactory;
    public $timestamps = false;

    protected $table = 'print_details_translations';

    protected $fillable = ['title','title_value','print_details_id','locale'];
}
