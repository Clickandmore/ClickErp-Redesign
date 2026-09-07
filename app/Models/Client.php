<?php

namespace App\Models;

 use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use Concerns\ResolvesMediaUrls;

    protected array $mediaUrlColumns = ['image'];

    protected $fillable = [
        'title',
        'image',
    ];

     
}
