<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value'
    ];

    public function getSetting($key){
        return @$this->where('key',$key)->first()->value;
    }

    public function setSetting($key ,$value){
        $data = $this->where('key',$key)->first();

        if($data){
            return $this->where('key',$key)->update(['value'=>$value]);
        }
        return $this->create(['value'=>$value,'key'=>$key]);
    }
}
