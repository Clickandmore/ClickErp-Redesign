<?php

namespace App\Models;

 use Illuminate\Database\Eloquent\Model;

class RequirementTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

 
    protected $fillable = [
        'title',
     ];

     
}
