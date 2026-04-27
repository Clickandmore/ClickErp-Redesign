<?php

namespace Nami\Hr\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueItemStockPair implements Rule
{
    protected $itemIds;
    protected $stockIds;

    public function __construct(array $itemIds, array $stockIds)
    {
        $this->itemIds = $itemIds;
        $this->stockIds = $stockIds;
    }

    public function passes($attribute, $value)
    {
        $pairs = [];

        foreach ($this->itemIds as $index => $itemId) {
            $stockId = $this->stockIds[$index] ?? null; // Get the corresponding stock ID

            if ($stockId) {
                $pair = "{$itemId}_{$stockId}";
                if (in_array($pair, $pairs)) {
                    return false; // If the pair already exists, validation fails
                }
                $pairs[] = $pair; // Add the pair to the array
            }
        }

        return true; // All pairs are unique
    }

    public function message()
    {
        return 'لا يمكن اختيار نفس المنتج في نفس المخزن مرتين !';
    }
}
