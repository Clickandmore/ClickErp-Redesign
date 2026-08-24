<?php

namespace App\Models\Concerns;

use BackedEnum;
use UnitEnum;

trait SerializesActivityEnums
{
    /**
     * Compatibility bridge for Spatie Activitylog on Laravel 8.
     *
     * The activity package calls this method while serializing enum casts,
     * but Laravel 8 does not provide it on the Eloquent model yet.
     */
    public function getStorableEnumValue(...$arguments)
    {
        $value = end($arguments);

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
    }
}
