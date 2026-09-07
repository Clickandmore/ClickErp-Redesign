<?php

namespace App\Models;

 use Illuminate\Database\Eloquent\Model;

class FeatureTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;


    protected $fillable = [
        'title',
        'description',
     ];


}
