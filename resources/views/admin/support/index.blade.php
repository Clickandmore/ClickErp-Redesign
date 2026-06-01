@extends('admin.layout.indexs.index')

@section('page-title', 'مركز المعرفة')

@section('style')
<style>
    .support-page {
        --support-border: #e5e7eb;
        --support-muted: #6b7280;
        --support-soft: #f8fafc;
    }
    .support-toolbar {
        background: #fff;
        border: 1px solid var(--support-border);
        border-radius: 8px;
        padding: 16px;
    }
    .support-search {
        position: relative;
    }
    .support-search .fa-search {
        position: absolute;
        top: 50%;
        right: 14px;
        transform: translateY(-50%);
        color: var(--support-muted);
    }
    .support-search input {
        padding-right: 42px;
        height: 46px;
    }
    .support-stat {
        border: 1px solid var(--support-border);
        border-radius: 8px;
        padding: 14px;
        background: #fff;
        min-height: 82px;
    }
    .support-stat strong {
        display: block;
        font-size: 24px;
        line-height: 1.2;
    }
    .support-category {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        border: 1px solid var(--support-border);
        border-radius: 8px;
        color: #111827;
        text-decoration: none;
        background: #fff;
    }
    .support-category.active,
    .support-category:hover {
        border-color: #0d6efd;
        color: #0d6efd;
        background: #f2f7ff;
    }
    .support-article {
        border: 1px solid var(--support-border);
        border-radius: 8px;
        background: #fff;
        height: 100%;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .support-article:hover {
        border-color: #b6c7ee;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
    }
    .support-article__body {
        padding: 16px;
        display: flex;
        flex-direction: column;
        min-height: 230px;
    }
    .support-article__title {
        font-size: 17px;
        line-height: 1.5;
        margin: 12px 0 8px;
        color: #111827;
    }
    .support-article__summary {
        color: var(--support-muted);
        line-height: 1.7;
        flex: 1;
    }
    .support-template {
        border: 1px solid #cfe2ff;
        border-radius: 8px;
        background: #f8fbff;
        padding: 16px;
    }
</style>
@endsection

@section('content')
<div class="page-body support-page">
    <div class="container-fluid">
        @include('admin.support._alerts')
        @include('admin.support._nav')

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="h3 mb-1 text-gray-800">
                    <i class="fas fa-book-open text-primary me-2"></i>
                    مركز المعرفة
                </h3>
                <div class="text-muted">دليل داخلي مرتب للعمليات، ويمكن دعم كل مقالة بلقطات شاشة وفيديوهات.</div>
            </div>
            <div class="d-flex gap-2 mt-3 mt-sm-0">
                <a href="{{ route('support.tickets.create') }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-ticket-alt"></i> فتح تذكرة
                </a>
                <a href="{{ route('support.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> إضافة دليل
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="support-stat">
                    <span class="text-muted">إجمالي المقالات</span>
                    <strong>{{ $totalArticles }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="support-stat">
                    <span class="text-muted">المقالات المنشورة</span>
                    <strong>{{ $publishedArticles }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="support-stat">
                    <span class="text-muted">مقالات بها صور أو فيديو</span>
                    <strong>{{ $mediaArticles }}</strong>
                </div>
            </div>
        </div>

        <div class="support-toolbar mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <label class="form-label">البحث في مركز المعرفة</label>
                    <div class="support-search">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="ابحث باسم العملية، رقم الخطوة، أو كلمة من الشرح">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label">التصنيف</label>
                    <select name="category" class="form-select">
                        <option value="">كل التصنيفات</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </div>
            </form>
        </div>

        <div class="row g-4">
            <div class="col-xl-3">
                <div class="mb-3">
                    <h6 class="fw-bold mb-2">التصنيفات</h6>
                    <div class="d-grid gap-2">
                        <a class="support-category {{ request('category') ? '' : 'active' }}" href="{{ route('support.index', request()->except('category', 'page')) }}">
                            <span>كل المقالات</span>
                            <span class="badge bg-light text-dark">{{ $totalArticles }}</span>
                        </a>
                        @foreach ($categoryCounts as $category => $count)
                            <a class="support-category {{ request('category') === $category ? 'active' : '' }}"
                                href="{{ route('support.index', array_merge(request()->except('page'), ['category' => $category])) }}">
                                <span>{{ $category }}</span>
                                <span class="badge bg-light text-dark">{{ $count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="support-template">
                    <h6 class="fw-bold mb-2"><i class="fas fa-photo-film me-1"></i> مقال تفصيلي مصور</h6>
                    <p class="text-muted mb-3">يمكن إنشاء مقالة واحدة لإدارة معينة، ثم رفع عدة صور وفيديوهات لها. الصور تظهر تلقائياً كـ لقطات شاشة والفيديوهات تظهر كمشغل داخل المقالة.</p>
                    <a href="{{ route('support.create') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-plus"></i> إنشاء مقال مصور
                    </a>
                </div>
            </div>

            <div class="col-xl-9">
                <div class="row g-3">
                    @forelse ($articles as $article)
                        <div class="col-md-6 col-xxl-4">
                            <div class="support-article">
                                <div class="support-article__body">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <span class="badge bg-light text-dark">{{ $article->category ?: 'عام' }}</span>
                                            @unless ($article->is_published)
                                                <span class="badge bg-warning text-dark">غير منشور</span>
                                            @endunless
                                        </div>
                                        <div class="text-muted small d-flex gap-2">
                                            @if ($article->attachments_count > 0)
                                                <span><i class="fas fa-paperclip"></i> {{ $article->attachments_count }}</span>
                                            @endif
                                            @if ($article->video_url)
                                                <span><i class="fas fa-play-circle"></i></span>
                                            @endif
                                        </div>
                                    </div>
                                    <h5 class="support-article__title">{{ $article->title }}</h5>
                                    <p class="support-article__summary">{{ \Illuminate\Support\Str::limit($article->summary ?: strip_tags($article->content), 155) }}</p>
                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                        <a href="{{ route('support.show', $article) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-book-open"></i> قراءة
                                        </a>
                                        <a href="{{ route('support.edit', $article) }}" class="btn btn-outline-warning btn-sm" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="support-article">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-circle-info fa-2x mb-2"></i>
                                    <p class="mb-0">لا توجد مقالات مطابقة للبحث الحالي</p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($articles->hasPages())
                    <div class="mt-4">
                        {{ $articles->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
