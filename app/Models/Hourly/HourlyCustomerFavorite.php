<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;

/** عاملة أو فريق مفضل للعميل؛ يُقترح أولًا عند الجدولة. */
class HourlyCustomerFavorite extends Model
{
    public const TYPES = ['maid', 'team'];

    protected $guarded = ['id'];
}
