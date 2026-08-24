<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Traits\API\ApiResponse;
use App\Models\ingaz\FastNew as objModel;
use Illuminate\Support\Facades\Schema;

/**
 * شريط آخر الأخبار المعروض في المواقع الخارجية.
 * يعيد العناوين المترجمة حسب لغة الطلب (هيدر lang)، والأحدث أولاً.
 */
class ApiFastNewController extends Controller
{
    use ApiResponse;

    public function index()
    {
        if (!Schema::hasTable('fast_news')) {
            return $this->successResponse([], __('api.All items'), 200);
        }

        $items = objModel::query()
            ->with('translations')
            ->latest()
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => trim((string) $row->title),
                ];
            })
            ->filter(function ($row) {
                return $row['title'] !== '';
            })
            ->values();

        return $this->successResponse($items, __('api.All items'), 200);
    }
}
