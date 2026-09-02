<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\BlogRequest;
use App\Models\ingaz\Blog;
use App\Models\ingaz\BlogFaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BlogController extends Controller
{
    public $folderPath = 'admin.ingaz.blogs';
    public $mainRoute = 'blogs';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = Blog::query()
                ->with('translations')
                ->when($request->filled('search_title'), function ($query) use ($request) {
                    $query->whereHas('translations', function ($inner) use ($request) {
                        $inner->where('title', 'like', '%' . $request->search_title . '%');
                    });
                })
                ->orderBy('sort_order')
                ->orderByDesc('published_at')
                ->orderByDesc('id');

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('image', function (Blog $row) {
                    return getImgTag($row->image, '90px', '70px');
                })
                ->addColumn('title_block', function (Blog $row) {
                    $published = $row->published_at ? $row->published_at->format('Y-m-d') : 'غير محدد';

                    return '<div class="d-flex flex-column gap-1">
                        <strong>' . e($row->title ?: 'بدون عنوان') . '</strong>
                        <span class="text-muted small">' . e(Str::limit(strip_tags((string) $row->excerpt), 120)) . '</span>
                        <span class="text-muted small"><i class="fa fa-link me-1"></i>' . e($row->slug) . '</span>
                        <span class="text-muted small"><i class="fa fa-calendar me-1"></i>' . e($published) . '</span>
                    </div>';
                })
                ->addColumn('meta_block', function (Blog $row) {
                    $parts = [];

                    if ($row->category) {
                        $parts[] = '<span class="badge bg-info text-white">' . e($row->category) . '</span>';
                    }

                    if ($row->is_featured) {
                        $parts[] = '<span class="badge bg-warning text-dark">مميّز</span>';
                    }

                    // اللغات التي كُتب لها عنوان فعلًا — يكشف المقالات الناقصة الترجمة.
                    foreach ($row->translations as $translation) {
                        if (filled($translation->title)) {
                            $parts[] = '<span class="badge bg-light text-dark border">' . e($translation->locale) . '</span>';
                        }
                    }

                    $parts[] = '<span class="badge bg-light text-dark border"><i class="fa fa-eye me-1"></i>'
                        . (int) $row->views_count . '</span>';

                    return '<div class="d-flex flex-wrap gap-1">' . implode('', $parts) . '</div>';
                })
                ->addColumn('status_toggle', function (Blog $row) {
                    $class = $row->is_active ? 'btn-outline-success' : 'btn-outline-secondary';
                    $label = $row->is_active ? 'منشور' : 'مخفي';

                    return '<button type="button" class="btn btn-sm ' . $class . ' blog-toggle-status" data-route="'
                        . route('blogs.toggle-status', $row->id) . '">' . $label . '</button>';
                })
                ->addColumn('actions', function (Blog $row) {
                    $actions = '';

                    if (@AdminGuardHasAnyPerm([\App\PermKeys::BLOGS_VIEW, \App\PermKeys::BLOGS_CONTROL])) {
                        $actions .= showButton(route($this->mainRoute . '.show', $row->id), $row->title);
                    }

                    if (@AdminGuardHasAnyPerm([\App\PermKeys::BLOGS_EDIT, \App\PermKeys::BLOGS_CONTROL])) {
                        $actions .= ' ' . editButton(route($this->mainRoute . '.edit', $row->id), $row->title);
                    }

                    if (@AdminGuardHasAnyPerm([\App\PermKeys::BLOGS_DELETE, \App\PermKeys::BLOGS_CONTROL])) {
                        $actions .= ' ' . deleteButton(route($this->mainRoute . '.destroy', $row->id));
                    }

                    return $actions;
                })
                ->rawColumns(['image', 'title_block', 'meta_block', 'status_toggle', 'actions'])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            'createRoute' => route($this->mainRoute . '.create'),
            'dataTableRoute' => route($this->mainRoute . '.index'),
            'bladeTitle' => 'المدونة والمقالات',
            'addButtonText' => 'مقال',
            'modalType' => 'modal-xl',
        ]);
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return jsonSuccess(['html' => view($this->folderPath . '.create', ['obj' => null])->render()]);
        }
    }

    public function store(BlogRequest $request)
    {
        $newFiles = [];

        try {
            DB::transaction(function () use ($request, &$newFiles) {
                $blog = new Blog();
                $this->fill($blog, $request, $newFiles);
                $blog->save();
                $this->syncFaqs($blog, $request);
            });
        } catch (\Throwable $exception) {
            $this->deleteFiles($newFiles);
            throw $exception;
        }

        return jsonSuccess();
    }

    public function show(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = Blog::with(['translations', 'faqs'])->findOrFail($id);

            return jsonSuccess(['html' => view($this->folderPath . '.show', compact('obj'))->render()]);
        }
    }

    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = Blog::with(['translations', 'faqs'])->findOrFail($id);

            return jsonSuccess(['html' => view($this->folderPath . '.edit', compact('obj'))->render()]);
        }
    }

    public function update(BlogRequest $request, $id)
    {
        $blog = Blog::findOrFail($id);
        $oldFiles = [];
        $newFiles = [];

        try {
            DB::transaction(function () use ($request, $blog, &$oldFiles, &$newFiles) {
                if ($request->hasFile('image') && $blog->image) {
                    $oldFiles[] = $blog->image;
                }

                $this->fill($blog, $request, $newFiles);
                $blog->save();
                $this->syncFaqs($blog, $request);
            });
        } catch (\Throwable $exception) {
            $this->deleteFiles($newFiles);
            throw $exception;
        }

        $this->deleteFiles($oldFiles);

        return jsonSuccess();
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);

        $image = $blog->image;
        $blog->delete();
        $this->deleteFiles([$image]);

        return jsonSuccess();
    }

    public function toggleStatus($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->is_active = ! $blog->is_active;
        $blog->save();

        return jsonSuccess(['is_active' => $blog->is_active]);
    }

    /**
     * يملأ الحقول الأساسية والترجمات. تُستدعى قبل save() في الإنشاء والتعديل معًا.
     */
    private function fill(Blog $blog, BlogRequest $request, array &$newFiles): void
    {
        $data = $request->validated();
        $defaultLocale = config('app.locale', 'ar');

        $blog->category = $data['category'] ?? null;
        $blog->is_active = $request->has('is_active') ? $request->boolean('is_active') : (bool) ($blog->is_active ?? true);
        $blog->is_featured = $request->boolean('is_featured');
        $blog->sort_order = (int) ($data['sort_order'] ?? 0);
        // مقال بلا تاريخ نشر يُعتبر منشورًا الآن، حتى لا يختفي من الموقع بلا سبب واضح.
        $blog->published_at = $data['published_at'] ?? $blog->published_at ?? now();

        $slug = trim((string) ($data['slug'] ?? ''));

        if ($slug === '') {
            $slug = $blog->slug ?: Blog::generateSlug(
                $data['title:' . $defaultLocale] ?? ($data['title:en'] ?? null),
                $blog->id
            );
        }

        $blog->slug = $slug;

        if ($request->hasFile('image')) {
            $blog->image = uploadFile($request->file('image'), 'images/blogs/', 1);
            $newFiles[] = $blog->image;
        }

        foreach (config('translatable.locales', ['ar']) as $locale) {
            $title = $data['title:' . $locale] ?? null;
            $content = $data['content:' . $locale] ?? null;

            // لغة لم تُملأ إطلاقًا لا تُنشأ لها ترجمة فارغة — الموقع يرجع
            // عندها إلى اللغة الافتراضية بدل عرض مقال بلا عنوان.
            if (blank($title) && blank($content)) {
                continue;
            }

            $blog->translateOrNew($locale)->title = $title;
            $blog->translateOrNew($locale)->excerpt = $data['excerpt:' . $locale] ?? null;
            $blog->translateOrNew($locale)->content = $content;
            $blog->translateOrNew($locale)->meta_title = $data['meta_title:' . $locale] ?? null;
            $blog->translateOrNew($locale)->meta_description = $data['meta_description:' . $locale] ?? null;
        }
    }

    /**
     * يحفظ الأسئلة الشائعة المرسلة مع النموذج ويحذف ما أزاله المحرر.
     *
     * يُستدعى بعد save() لأن السؤال الجديد يحتاج blog_id، ولأن الصفوف تصل
     * كمصفوفة مستقلة عن حقول المقال نفسه.
     */
    private function syncFaqs(Blog $blog, BlogRequest $request): void
    {
        $locales = config('translatable.locales', ['ar']);
        $rows = $request->input('faqs', []);

        if (!is_array($rows)) {
            $rows = [];
        }

        $keptIds = [];
        $order = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            // صف لم يُكتب فيه أي سؤال بأي لغة = صف فارغ تركه المحرر، يُتجاهل
            // بدل حفظ سؤال بلا نص.
            $hasQuestion = false;

            foreach ($locales as $locale) {
                if (filled($row['question:' . $locale] ?? null)) {
                    $hasQuestion = true;
                    break;
                }
            }

            if (!$hasQuestion) {
                continue;
            }

            $faq = null;

            if (filled($row['id'] ?? null)) {
                // القيد على blog_id يمنع تعديل سؤال يخص مقالًا آخر عبر id مزوّر.
                $faq = $blog->faqs()->whereKey($row['id'])->first();
            }

            if (!$faq) {
                $faq = new BlogFaq(['blog_id' => $blog->id]);
            }

            $faq->blog_id = $blog->id;
            $faq->sort_order = $order++;
            $faq->save();

            $emptyLocales = [];

            foreach ($locales as $locale) {
                $question = $row['question:' . $locale] ?? null;

                // لغة بلا سؤال لا تُنشأ لها ترجمة — الموقع يرجع عندها إلى
                // اللغة الافتراضية، كما يفعل مع محتوى المقال.
                if (blank($question)) {
                    $emptyLocales[] = $locale;

                    continue;
                }

                $faq->translateOrNew($locale)->question = $question;
                $faq->translateOrNew($locale)->answer = $row['answer:' . $locale] ?? null;
            }

            $faq->save();

            // حذف ترجمات اللغات التي أفرغها المحرر يأتي بعد save() عمدًا:
            // deleteTranslations() يعيد تحميل علاقة translations، وهو ما كان
            // ليمحو الترجمات التي أُنشئت للتو في الذاكرة ولم تُحفظ بعد.
            if ($emptyLocales) {
                $faq->deleteTranslations($emptyLocales);
            }

            $keptIds[] = $faq->id;
        }

        // get()->each->delete() لا delete() المجمّع: الأخير يتخطى أحداث الموديل،
        // فتبقى ترجمات السؤال المحذوف يتيمة في القواعد بلا قيود foreign key.
        $blog->faqs()->whereNotIn('id', $keptIds ?: [0])->get()->each->delete();
        $blog->unsetRelation('faqs');
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            deleteFile($path);
        }
    }
}
