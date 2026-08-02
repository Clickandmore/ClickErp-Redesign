@csrf

@if (isset($article))
    @method('PUT')
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">عنوان الدليل</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $article->title ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">التصنيف</label>
        <input type="text" name="category" class="form-control" value="{{ old('category', $article->category ?? '') }}" placeholder="مثال: العقود، الطلبات، العملاء">
    </div>
    <div class="col-md-8">
        <label class="form-label">ملخص سريع</label>
        <textarea name="summary" class="form-control" rows="3">{{ old('summary', $article->summary ?? '') }}</textarea>
    </div>
    <div class="col-md-2">
        <label class="form-label">الترتيب</label>
        <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $article->sort_order ?? 0) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label d-block">النشر</label>
        <div class="form-check form-switch mt-2">
            <input type="checkbox" name="is_published" value="1" class="form-check-input" id="is_published"
                {{ old('is_published', $article->is_published ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_published">منشور</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">شرح العملية</label>
        <textarea name="content" class="form-control" rows="12" required>{{ old('content', $article->content ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">رابط فيديو خارجي</label>
        <input type="url" name="video_url" class="form-control" value="{{ old('video_url', $article->video_url ?? '') }}" placeholder="https://...">
    </div>
    <div class="col-md-6">
        <label class="form-label">مرفقات صور أو فيديو أو ملفات</label>
        <input type="file" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.pdf,.doc,.docx">
        <div class="form-text">الصور تظهر داخل صفحة الدليل كـ لقطات شاشة، والفيديوهات تظهر كمقاطع قابلة للتشغيل.</div>
    </div>
</div>

@if (isset($article) && $article->attachments->isNotEmpty())
    <div class="mt-4">
        <h6 class="fw-bold">المرفقات الحالية</h6>
        <div class="row g-2">
            @foreach ($article->attachments as $attachment)
                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                        <a href="{{ showFile($attachment->file_path) }}" target="_blank">
                            <i class="fas fa-paperclip me-1"></i>{{ $attachment->file_name }}
                        </a>
                        <form action="{{ route('support.attachments.destroy', $attachment) }}" method="POST" onsubmit="return confirm('حذف هذا المرفق؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i> حفظ
    </button>
    <a href="{{ route('support.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> رجوع
    </a>
</div>
