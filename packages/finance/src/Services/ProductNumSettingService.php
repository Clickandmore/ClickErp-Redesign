<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\ProductNumSetting as Model;

class ProductNumSettingService extends MainService
{

    function __construct(Model $model,private CategoryService $categoryService)
    {
        $this->model = $model;
    }

    public function getMainCategories()
    {
        return $this->categoryService->getWhere(['parent_id' => null]);
    }

    public function getSubCategories()
    {
        return $this->categoryService->getSubCategoriesAndMainWithoutChildren();
    }

    public function getWhereCategory($request){
        return $this->model->
        where('category_id', $request['sub_category_id'])
        ->orWhere('category_id', $request['main_category_id'])
        ->first();
    }

    public function storeSetting($data)
    {
        if($data['type'] == 'allProducts'){
            return $this->model->create([
                'type' => $data['type'],
                'code_string' => $data['code_string'],
                'code' => $data['code']
            ]);
        }else if($data['type'] == 'mainCategories'){
            foreach ($data['mainCategories'] as $key => $item) {
                $this->model->create([
                    'type' => $data['type'],
                    'code_string' => $item['code_string'],
                    'code' => $item['code'],
                    'category_id' => $key
                ]);
            }
        }else if($data['type'] == 'subCategories'){
            foreach ($data['subCategories'] as $key => $item)  {
                $this->model->create([
                    'type' => $data['type'],
                    'code_string' => $item['code_string'],
                    'code' => $item['code'],
                    'category_id' => $key
                ]);
            }
        }

        return true;
    }

    public function deleteAll()
    {
        return $this->model->truncate();
    }
}
