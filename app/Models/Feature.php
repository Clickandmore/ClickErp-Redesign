<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable, Concerns\ResolvesMediaUrls;

    protected array $mediaUrlColumns = ['image_file'];

    protected $fillable = [
        'image',
        'image_file',
    ];

    protected $appends = [
        'image_url',
    ];

    public $translatedAttributes = [
        'title',
        'description',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_file ? showFile($this->image_file) : null;
    }



}
