<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyCategoryNote, HourlyServiceCategory};
use App\Services\Hourly\ServiceCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** تبويب «ملاحظات نموذج الطلب»: إضافة وترتيب وحذف الملاحظات المخصصة. */
class HourlyCategoryNoteController extends Controller
{
    public function __construct(private ServiceCategoryService $service) {}

    public function store(Request $request, HourlyServiceCategory $category)
    {
        $data = $request->validate(['text_ar' => ['required', 'string', 'max:1000'], 'text_en' => ['nullable', 'string', 'max:1000']], [], ['text_ar' => __('hourly_category_ui.note_ar'), 'text_en' => __('hourly_category_ui.note_en')]);
        $note = $category->notes()->create($data + ['sort_order' => (int) $category->notes()->max('sort_order') + 1]);
        $this->service->audit($category, 'notes', 'add', null, $note->only(['text_ar', 'text_en']));
        return $this->back($category, __('hourly_category_ui.note_added'));
    }

    public function reorder(Request $request, HourlyServiceCategory $category)
    {
        $ids = $category->notes()->pluck('id')->all();
        $data = $request->validate(['ids' => ['required', 'array', 'size:'.count($ids)], 'ids.*' => ['integer', 'distinct', Rule::in($ids)]]);
        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $position => $id) HourlyCategoryNote::whereKey($id)->update(['sort_order' => $position + 1]);
        });
        $this->service->audit($category, 'notes', 'reorder', $ids, array_map('intval', $data['ids']));
        return $request->expectsJson() ? response()->json(['message' => __('hourly_category_ui.order_saved')]) : $this->back($category, __('hourly_category_ui.order_saved'));
    }

    public function destroy(HourlyServiceCategory $category, HourlyCategoryNote $note)
    {
        abort_unless((int) $note->service_category_id === $category->id, 404);
        $this->service->audit($category, 'notes', 'remove', $note->only(['text_ar', 'text_en']), null);
        $note->delete();
        return $this->back($category, __('hourly_category_ui.note_deleted'));
    }

    private function back(HourlyServiceCategory $category, string $message)
    {
        return redirect()->to(route('admin.hourly-service-categories.show', $category).'#notes')->with('success', $message);
    }
}
