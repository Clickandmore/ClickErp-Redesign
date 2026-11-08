@extends('admin.layout.indexs.index')
@section('page-title', 'سجل مشاركة السير')
@section('content')
<div class="container-fluid py-4" dir="rtl">
    <h3>سجل مشاركة السير عبر واتساب</h3>
    @php $labels = ['queued' => 'بانتظار التنفيذ', 'processing' => 'جارٍ الإرسال', 'accepted' => 'قبل المزود الإرسال', 'partial' => 'إرسال جزئي', 'failed' => 'فشل الإرسال', 'needs_review' => 'تحتاج مراجعة']; @endphp
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>المشاركة</th><th>الموظف</th><th>العميل</th><th>واتساب</th><th>تاريخ التأكيد</th><th>الحالة</th></tr></thead>
        <tbody>@forelse ($shares as $share)
            <tr><td><a href="{{ route('maid-cv-shares.show', $share->id) }}">#{{ $share->id }}</a></td><td>{{ $share->admin_name }}</td><td>{{ $share->client_name }}</td><td><bdi>{{ $share->phone }}</bdi></td><td>{{ $share->confirmed_at }}</td><td>{{ $labels[$share->status] ?? $share->status }}</td></tr>
        @empty<tr><td colspan="6">لا توجد مشاركات.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $shares->links() }}
</div>
@endsection
