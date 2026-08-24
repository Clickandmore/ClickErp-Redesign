<?php

namespace Nami\Finance\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class PrincipleImage extends Finance
{
    protected $table ='fn_principle_images';
    protected $fillable =['principle_id','file'];

    public function principle()
    {
        return $this->belongsTo(Principle::class, 'principle_id');
    }
}
