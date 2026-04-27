<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrincipleHistory extends Model
{
    protected $table = 'fn_principle_history';
    protected $fillable =['principle_id','from_website_id','to_website_id'];

    public function to_website()
    {
        return $this->belongsTo(Principle::class, 'to_website_id');
    }

    public function from_website()
    {
        return $this->belongsTo(PrincipleWebsite::class, 'from_website_id');
    }

    public function principle()
    {
        return $this->belongsTo(PrincipleWebsite::class, 'principle_id');
    }

}
