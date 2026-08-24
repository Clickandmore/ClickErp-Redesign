<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankTranslation extends Finance
{
    ## Initialization
    // use HasFactory;

    protected $table = "bank_translations";
    protected $fillable = ['bank_id', 'name'];

    ## Relations

}
