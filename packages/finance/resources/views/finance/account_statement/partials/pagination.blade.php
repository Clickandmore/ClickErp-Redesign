@if ($data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $data->hasPages())
    <div class="statement-pagination col-12 mt-3" dir="rtl">
        <p class="text-muted">عرض {{ $data->firstItem() }} – {{ $data->lastItem() }} من {{ $data->total() }} حركة</p>
        {{ $data->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
@endif
