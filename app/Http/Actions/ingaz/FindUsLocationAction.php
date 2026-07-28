<?php

namespace App\Http\Actions\ingaz;

use App\Models\FindUsLocation;
use Illuminate\Database\Eloquent\Collection;

class FindUsLocationAction
{
    public function ordered(): Collection
    {
        return FindUsLocation::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
