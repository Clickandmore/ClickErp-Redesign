<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Traits\API\ApiResponse;
use App\Models\ingaz\FastNew as objModel;
use Illuminate\Support\Facades\Schema;

/**
 * شريط آخر الأخبار المعروض في المواقع الخارجية.
 * يعيد العناوين المترجمة حسب لغة الطلب (هيدر lang) مع صورة كل خبر إن وُجدت، والأحدث أولاً.
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
            ->where('is_visible', true)
            ->with('translations')
            ->latest()
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => trim((string) $row->title),
                    // رابط مطلق للصورة الاختيارية، أو null إن لم تُرفع صورة
                    'image' => showFile($row->image ?? null),
                ];
            })
            ->filter(function ($row) {
                return $row['title'] !== '';
            })
            ->values();

        return $this->successResponse($items, __('api.All items'), 200);
    }
}
