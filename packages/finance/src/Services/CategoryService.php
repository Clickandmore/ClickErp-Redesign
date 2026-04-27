<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\MainService;
use Nami\Finance\Models\Category;

class CategoryService extends MainService
{
    public function __construct(Category $model)
    {
        $this->model = $model;
    }

    public function getCategoryTypes()
    {
        return CategoryTypes::cases();
    }

    public function getSubCategoriesAndMainWithoutChildren()
    {
        return Category::whereNotNull('parent_id')
            ->orWhereDoesntHave('children')
            ->get();
    }
}
