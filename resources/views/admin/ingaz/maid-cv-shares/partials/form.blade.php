{{-- نموذج مشاركة السير: يُستخدم في صفحة الإنشاء وفي مودال صفحة العاملات --}}
<form method="POST" action="{{ route('maid-cv-shares.store') }}" class="cv-share-form" dir="rtl" novalidate>
    @csrf
    <div class="cv-share-errors alert alert-danger d-none" role="alert"></div>

    @unless ($whatsapp->isConfigured())
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> أكمل إعدادات واتساب أولًا.
        </div>
    @endunless

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <h6 class="fw-bold mb-3"><span class="badge rounded-pill bg-success me-1">1</span> العميل ورقم الواتساب</h6>

                <label class="form-label small text-muted">ابحث بالاسم أو الجوال</label>
                <div class="input-group mb-1">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="search" class="form-control cv-client-search" minlength="2" maxlength="100"
                        placeholder="حرفان على الأقل…">
                    <button type="button" class="btn btn-outline-success cv-client-search-button">بحث</button>
                </div>
                <div class="small text-muted mb-3 cv-search-status" role="status"></div>

                <label class="form-label small text-muted">العميل</label>
                <select name="client_id" class="form-select mb-3 cv-client" required>
                    <option value="">اختر العميل</option>
                </select>

                <label class="form-label small text-muted"><i class="fab fa-whatsapp text-success"></i> رقم واتساب</label>
                <select name="phone" class="form-select mb-3 cv-phone" required></select>

                @if (in_array($whatsapp->driver(), ['ultramsg', 'clickchat'], true) && $whatsapp->availableSenders() !== [])
                    <label class="form-label small text-muted">رقم الإرسال</label>
                    <select name="sender_key" class="form-select" required>
                        @foreach ($whatsapp->availableSenders() as $sender)
                            <option value="{{ $sender['key'] }}" @selected($sender['key'] === $whatsapp->defaultSenderKey())>
                                {{ $sender['name'] }} {{ $sender['phone_number'] }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="border rounded-3 p-3 h-100">
                <h6 class="fw-bold mb-1 d-flex justify-content-between align-items-center">
                    <span><span class="badge rounded-pill bg-success me-1">2</span> السير المحددة</span>
                    <span class="badge bg-success-subtle text-success border border-success cv-share-count">{{ $maids->count() }}</span>
                </h6>
                <p class="small text-muted mb-2">أزل علامة التحديد لاستبعاد سيرة.</p>
                <div class="d-flex flex-column gap-2" style="max-height:320px;overflow-y:auto">
                    @foreach ($maids as $maid)
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0" style="cursor:pointer">
                            <input type="checkbox" class="form-check-input m-0 cv-share-maid" name="maid_ids[]" value="{{ $maid->id }}" checked>
                            <span class="flex-grow-1">
                                <span class="fw-semibold d-block">{{ $maid->name }}</span>
                                <span class="small text-muted">
                                    {{ $maid->cv_code ?: $maid->id }} · {{ $maid->nationality?->sub_title ?: $maid->nationality?->title }}
                                </span>
                            </span>
                            <span class="badge {{ $maid->isRentalCv() ? 'bg-info' : 'bg-primary' }}">
                                {{ $maid->isRentalCv() ? 'إيجار' : 'استقدام' }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border small mt-3 mb-0">
        <i class="fas fa-info-circle text-primary"></i>
        سيتم تجهيز ملفات PDF ثم عرض الرسالة والمرفقات للمراجعة قبل تأكيد الإرسال.
        الإرسال عبر Meta أو Widers يتطلب نافذة محادثة متاحة مع العميل.
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-light d-none cv-share-cancel" data-bs-dismiss="modal">إلغاء</button>
        <button type="submit" class="btn btn-success px-4 cv-share-submit" @disabled(!$whatsapp->isConfigured())>
            <i class="fas fa-eye me-1"></i> تجهيز ومعاينة المشاركة
        </button>
    </div>
</form>
