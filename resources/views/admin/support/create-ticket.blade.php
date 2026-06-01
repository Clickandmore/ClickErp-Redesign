@extends('admin.layout.indexs.index')

@section('page-title', 'فتح تذكرة دعم')

@section('content')
<div class="page-body">
    <div class="container-fluid">
        @include('admin.support._alerts')
        @include('admin.support._nav')

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-ticket-alt me-1"></i> فتح تذكرة دعم</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('support.tickets.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">الموضوع</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">التصنيف</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="مثال: صلاحيات، عقد، تقرير">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الأولوية</label>
                            <select name="priority" class="form-select" required>
                                <option value="normal">عادية</option>
                                <option value="low">منخفضة</option>
                                <option value="high">مرتفعة</option>
                                <option value="urgent">عاجلة</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">وصف المشكلة أو الطلب</label>
                            <textarea name="message" rows="8" class="form-control" required>{{ old('message') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i> إرسال التذكرة
                        </button>
                        <a href="{{ route('support.tickets') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> رجوع
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
