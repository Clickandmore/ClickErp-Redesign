<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ProductNumSettingEnum;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\ProductNumSettingService;

class GetCodeController extends Controller
{
    public string $folderPath = "finance::finance.items.create";

    public function store(Request $request, ProductNumSettingService $productNumSettingService, ItemService $itemService) {
        $categoryId = $request->input('category_id');

        $MainCategories = $productNumSettingService->getWhereFirst(['type' => ProductNumSettingEnum::MainCategories->value]);
        $SubCategories = $productNumSettingService->getWhereFirst(['type' => ProductNumSettingEnum::SubCategories->value]);
        if ($MainCategories || $SubCategories) {
            if ($MainCategories) {
                $latestItem = $itemService->getWhere([
                    'main_category_id' => $categoryId
                ])->sortByDesc('id')->first();
            }
            elseif ($SubCategories) {
                $latestItem = $itemService->getItemsFromCategoryOrSubCategory($categoryId);
                // $latestItem = $itemService->getWhere([
                //     'sub_category_id' => $categoryId
                // ])->sortByDesc('id')->first();
            }
            if ($latestItem) {
                $newCode = $latestItem->code + 1;
                $codeString = $latestItem->code_string;

                return response()->json(['code' => $codeString . $newCode]);
            } else {
                $codes = $productNumSettingService->getWhere(['category_id' => $categoryId]);
                if ($codes->count() > 0) {
                    return response()->json(['code' => $codes->first()->code_string . $codes->first()->code]);
                }
            }

        }

        // إذا لم يتم العثور على أي بيانات
        return response()->json(['code' => null]);
    }

}
