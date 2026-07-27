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
        return asset('storage/' . $this->image) ?? null;
    }

    public function getImageVisionURLAttribute()
    {
        return asset('storage/' . $this->image_vision) ?? null;
    }

    public function getImageMessageURLAttribute()
    {
        return asset('storage/' . $this->image_message) ?? null;
    }

    public function getImageValueURLAttribute()
    {
        return asset('storage/' . $this->image_value) ?? null;
    }
}
