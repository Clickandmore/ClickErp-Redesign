<?php

namespace App\Models;

 use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use Concerns\ResolvesMediaUrls;

    protected array $mediaUrlColumns = ['image'];

    protected $fillable = [
        'title',
        'image',
    ];

     
}
