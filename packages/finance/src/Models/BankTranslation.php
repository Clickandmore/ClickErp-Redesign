<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTranslation extends Model
{
    ## Initialization
    // use HasFactory;

    protected $table = "bank_translations";
    protected $fillable = ['bank_id', 'name'];

    ## Relations

}
