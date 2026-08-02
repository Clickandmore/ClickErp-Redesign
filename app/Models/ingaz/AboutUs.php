<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    use Translatable,HasFactory;

    protected $guarded = [];

    public $translatedAttributes = ['title','description' , 'mission','vision','value'];
    protected $translationForeignKey = 'about_us_id';
    public $translationModel = AboutUsTranslation::class;
    protected $with = ['translations'];

    protected $appends = [
        'image_url',
        'image_vision_url',
        'image_message_url',
        'image_value_url',
    ];

    public function getImageURLAttribute()
    {
        return showFile($this->image);
    }

    public function getImageVisionURLAttribute()
    {
        return showFile($this->image_vision);
    }

    public function getImageMessageURLAttribute()
    {
        return showFile($this->image_message);
    }

    public function getImageValueURLAttribute()
    {
        return showFile($this->image_value);
    }
}
