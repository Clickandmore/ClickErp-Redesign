<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class RequirementItem extends Model implements TranslatableContract
{
    use \App\Models\Concerns\AuditsChanges;

    use Translatable;

    protected $fillable = [
        'requirement_id',
        'icon',
        'sort_order',
    ];

    public $translatedAttributes = ['text', 'description'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $with = ['translations'];

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }
}
