<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('support.index') }}" class="btn {{ request()->routeIs('support.index', 'support.show', 'support.create', 'support.edit') ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
        <i class="fas fa-book-open me-1"></i> مركز المعرفة
    </a>
    <a href="{{ route('support.tickets') }}" class="btn {{ request()->routeIs('support.tickets', 'support.tickets.create') ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
        <i class="fas fa-ticket-alt me-1"></i> تذاكر الدعم
    </a>
</div>
