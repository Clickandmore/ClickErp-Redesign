<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\BlogResource;
use App\Models\ingaz\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * مقالات المدونة للموقع الخارجي.
 *
 * تُقرأ اللغة من هيدر/بارامتر lang عبر middleware 'lang' المطبَّق على المجموعة،
 * فتعود الترجمة المناسبة تلقائيًا من astrotomic.
 */
class ApiBlogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 9);
        $perPage = max(1, min($perPage, 50));

        $query = Blog::query()
            ->published()
            ->orderedForSite()
            ->when($request->filled('category'), function ($inner) use ($request) {
                $inner->where('category', $request->query('category'));
            })
            ->when($request->boolean('featured'), function ($inner) {
                $inner->where('is_featured', true);
            })
            ->when($request->filled('search'), function ($inner) use ($request) {
                $search = $request->query('search');
                $inner->whereHas('translations', function ($translations) use ($search) {
                    $translations->where('title', 'like', '%' . $search . '%')
                        ->orWhere('excerpt', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('exclude_slug'), function ($inner) use ($request) {
                $inner->where('slug', '!=', $request->query('exclude_slug'));
            });

        $blogs = $query->paginate($perPage);

        return apiResponse(true, __('api.All items'), [
            'data' => BlogResource::collection($blogs->items()),
            'current_page' => $blogs->currentPage(),
            'last_page' => $blogs->lastPage(),
            'per_page' => $blogs->perPage(),
            'total' => $blogs->total(),
            // التصنيفات المتاحة تُرسل مع القائمة حتى يبني الموقع فلتر التصنيفات
            // دون طلب إضافي.
            'categories' => $this->categories(),
        ], 200);
    }

    public function show(Request $request, string $slug)
    {
        $blog = Blog::query()->published()->with('faqs')->where('slug', $slug)->first();

        if (!$blog) {
            return apiResponse(false, __('api.not found'), null, 404);
        }

        // زيادة مباشرة بدون save() حتى لا تُحدَّث updated_at (وهي تُستخدم في
        // lastmod داخل sitemap.xml) عند كل مشاهدة.
        DB::table('blogs')->where('id', $blog->id)->increment('views_count');

        $related = Blog::query()
            ->published()
            ->where('id', '!=', $blog->id)
            ->when($blog->category, fn($query) => $query->where('category', $blog->category))
            ->orderedForSite()
            ->limit(3)
            ->get();

        // لو لم يكفِ التصنيف نفسه، نكمل بأحدث المقالات أيًّا كان تصنيفها.
        if ($related->count() < 3) {
            $related = $related->concat(
                Blog::query()
                    ->published()
                    ->where('id', '!=', $blog->id)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->orderedForSite()
                    ->limit(3 - $related->count())
                    ->get()
            );
        }

        return apiResponse(true, __('api.All items'), [
            'blog' => (new BlogResource($blog))->withContent(),
            'related' => BlogResource::collection($related),
        ], 200);
    }

    /**
     * روابط كل المقالات المنشورة — يستهلكها sitemap.xml في الموقع الخارجي.
     */
    public function slugs()
    {
        $blogs = Blog::query()
            ->published()
            ->orderedForSite()
            ->get(['id', 'slug', 'updated_at', 'published_at']);

        return apiResponse(true, __('api.All items'), $blogs->map(fn(Blog $blog) => [
            'slug' => $blog->slug,
            'updated_at' => optional($blog->updated_at)->toDateString(),
            'published_at' => optional($blog->published_at)->toDateString(),
        ])->values(), 200);
    }

    private function categories(): array
    {
        return Blog::query()
            ->published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values()
            ->all();
    }
}
