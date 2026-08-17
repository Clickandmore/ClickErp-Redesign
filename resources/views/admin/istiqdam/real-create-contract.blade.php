@extends('admin.layout.indexs.index')

@section('page-title', 'إنشاء عقد عبر منصة تكامل')

@section('content')
<div class="page-body p-4 text-dark" dir="rtl">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1">إنشاء عقد استقدام حقيقي</h4>
                <div class="text-muted">تُرسل البيانات مباشرة إلى منصة تكامل/قوى.</div>
            </div>
            <a href="{{ route('istiqdam.index') }}" class="btn btn-outline-secondary">العودة إلى منصة الاستقدام</a>
        </div>

        <div class="alert {{ $apiEnvironment === 'production' ? 'alert-danger' : 'alert-warning' }}">
            البيئة النشطة: <strong>{{ $apiEnvironment }}</strong>.
            @if ($apiEnvironment === 'production')
                الإرسال سينشئ عقدًا فعليًا في بيئة الإنتاج.
            @else
                استخدم بيانات اختبار معتمدة من تكامل فقط.
            @endif
        </div>

        <div id="contract-result" class="d-none" role="alert"></div>

        <form id="takamol-contract-form" method="POST" action="{{ route('istiqdam.contracts.store') }}">
            @csrf

            <div class="card mb-4">
                <div class="card-header"><strong>الباقة والعقد</strong></div>
                <div class="card-body row g-3">
                    <div class="col-lg-6">
                        <label class="form-label" for="package_id">الباقة <span class="text-danger">*</span></label>
                        <select class="form-select" id="package_id" name="package_id" required>
                            <option value="">اختر باقة من تكامل</option>
                            @foreach ($packages as $package)
                                @php
                                    $packageId = $package['id'] ?? $package['packageId'] ?? null;
                                    $occupation = data_get($package, 'occupation.name.ar', data_get($package, 'occupation', '-'));
                                    $nationality = data_get($package, 'nationality.name.ar', data_get($package, 'nationality', '-'));
                                    $price = data_get($package, 'price.gross', data_get($package, 'priceGross'));
                                @endphp
                                @if ($packageId)
                                    <option value="{{ $packageId }}" @selected((string) request('package_id') === (string) $packageId)>
                                        #{{ $packageId }} — {{ $occupation }} — {{ $nationality }}
                                        @if (is_numeric($price)) — {{ number_format($price, 2) }} ر.س @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div class="form-text">تم جلب {{ count($packages) }} باقة من المنصة.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="start_at">تاريخ بداية العقد <span class="text-danger">*</span></label>
                        <input class="form-control" type="date" id="start_at" name="start_at" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="special_specification">المواصفات الخاصة</label>
                        <input class="form-control" id="special_specification" name="special_specification" placeholder="اختياري">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>بيانات المستفيد</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="beneficiary_id">رقم الهوية <span class="text-danger">*</span></label>
                        <input class="form-control" id="beneficiary_id" name="beneficiary_id" inputmode="numeric" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="beneficiary_birthdate">تاريخ الميلاد <span class="text-danger">*</span></label>
                        <input class="form-control" type="date" id="beneficiary_birthdate" name="beneficiary_birthdate" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="contact_person_name">اسم المستفيد/مسؤول التواصل <span class="text-danger">*</span></label>
                        <input class="form-control" id="contact_person_name" name="contact_person_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="beneficiary_city">المدينة</label>
                        <input class="form-control" id="beneficiary_city" name="beneficiary_city">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="beneficiary_district">الحي</label>
                        <input class="form-control" id="beneficiary_district" name="beneficiary_district">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="beneficiary_postal_code">الرمز البريدي</label>
                        <input class="form-control" id="beneficiary_postal_code" name="beneficiary_postal_code">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="beneficiary_short_address">العنوان المختصر</label>
                        <input class="form-control" id="beneficiary_short_address" name="beneficiary_short_address">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>بيانات التواصل</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="contact_person_phone_number">هاتف مسؤول التواصل <span class="text-danger">*</span></label>
                        <input class="form-control" id="contact_person_phone_number" name="contact_person_phone_number" placeholder="+9665XXXXXXXX" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="contact_mobile_number">الجوال <span class="text-danger">*</span></label>
                        <input class="form-control" id="contact_mobile_number" name="contact_mobile_number" placeholder="+9665XXXXXXXX" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="contact_email">البريد الإلكتروني</label>
                        <input class="form-control" type="email" id="contact_email" name="contact_email">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contact_second_number">رقم ثانٍ</label>
                        <input class="form-control" id="contact_second_number" name="contact_second_number">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contact_home_number">هاتف المنزل</label>
                        <input class="form-control" id="contact_home_number" name="contact_home_number">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contact_work_number">هاتف العمل</label>
                        <input class="form-control" id="contact_work_number" name="contact_work_number">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contact_redirect">رقم التحويلة</label>
                        <input class="form-control" id="contact_redirect" name="contact_redirect">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>الدفع والالتزامات</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="payment_method">طريقة الدفع <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="CASH">نقدي</option>
                            <option value="BANK_TRANSFER">تحويل بنكي</option>
                            <option value="ONLINE">دفع إلكتروني</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="down_payment_amount">مبلغ الدفعة <span class="text-danger">*</span></label>
                        <input class="form-control" type="number" step="0.01" min="0" id="down_payment_amount" name="down_payment_amount" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="down_payment_due_date">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                        <input class="form-control" type="date" id="down_payment_due_date" name="down_payment_due_date" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="discount_percentage">نسبة الخصم</label>
                        <input class="form-control" type="number" step="0.01" min="0" max="100" id="discount_percentage" name="discount_percentage" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="notice_period">مدة الإشعار</label>
                        <input class="form-control" type="number" min="0" id="notice_period" name="notice_period" value="24">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="replacement_times">مرات الاستبدال</label>
                        <input class="form-control" type="number" min="0" id="replacement_times" name="replacement_times" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="travel_accompaniment_days">أيام المرافقة</label>
                        <input class="form-control" type="number" min="0" id="travel_accompaniment_days" name="travel_accompaniment_days" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="travel_accompaniment_hours">ساعات المرافقة</label>
                        <input class="form-control" type="number" min="0" id="travel_accompaniment_hours" name="travel_accompaniment_hours" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="laborer_transportation_costs">تكلفة النقل</label>
                        <input class="form-control" type="number" step="0.01" min="0" id="laborer_transportation_costs" name="laborer_transportation_costs" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="administrative_fee_percent">الرسوم الإدارية %</label>
                        <input class="form-control" type="number" step="0.01" min="0" id="administrative_fee_percent" name="administrative_fee_percent" value="2.5">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="termination_fee">رسوم الإنهاء</label>
                        <input class="form-control" type="number" step="0.01" min="0" id="termination_fee" name="termination_fee" value="2.5">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('istiqdam.index') }}" class="btn btn-light">إلغاء</a>
                <button class="btn btn-success" id="submit-contract" type="submit">إرسال العقد إلى تكامل</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
document.getElementById('takamol-contract-form').addEventListener('submit', async function (event) {
    event.preventDefault();

    const environment = @json($apiEnvironment);
    const warning = environment === 'production'
        ? 'أنت على بيئة الإنتاج. سيتم إنشاء عقد فعلي. هل تريد المتابعة؟'
        : 'سيتم إرسال بيانات الاختبار إلى منصة تكامل. هل تريد المتابعة؟';

    if (!window.confirm(warning)) return;

    const button = document.getElementById('submit-contract');
    const resultBox = document.getElementById('contract-result');
    button.disabled = true;
    button.textContent = 'جاري الإرسال...';
    resultBox.className = 'alert alert-info';
    resultBox.textContent = 'جاري إرسال العقد إلى منصة تكامل...';

    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: new FormData(this),
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            const validation = data.errors
                ? Object.values(data.errors).flat().join(' — ')
                : (data.message || 'فشل إنشاء العقد.');
            throw new Error(validation);
        }

        const platformNumber = data.platform_contract_number || data.data?.number || data.data?.contractId || data.data?.id || '';
        resultBox.className = 'alert alert-success';
        resultBox.textContent = `${data.message}${platformNumber ? ` رقم المنصة: ${platformNumber}` : ''}`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        resultBox.className = 'alert alert-danger';
        resultBox.textContent = error.message || 'تعذر الاتصال بالخادم.';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally {
        button.disabled = false;
        button.textContent = 'إرسال العقد إلى تكامل';
    }
});
</script>
@endsection
