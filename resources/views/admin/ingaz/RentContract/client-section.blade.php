<!-- معلومات العميل -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-user me-2"></i>معلومات العميل</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- المعلومات الأساسية -->
            <div class="col-md-6 mb-3">
                <label for="client_name" class="form-label">اسم العميل <span class="required">*</span></label>
                <input type="text" class="form-control" id="client_name" name="client_name" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="client_phone" class="form-label">رقم الجوال <span class="required">*</span></label>
                <input type="tel" class="form-control" id="client_phone" name="client_phone" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="client_nationality_id" class="form-label">الجنسية <span class="required">*</span></label>
                <select class="form-select select2" id="client_nationality_id" name="client_nationality_id" required>
                    <option value="" disabled selected>اختر الجنسية...</option>
                    @foreach($nationalities as $nationality)
                        <option value="{{$nationality->id}}">
                            {{ $nationality->translations->first()->title ?? $nationality->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="client_email" class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" id="client_email" name="client_email">
            </div>
            <div class="col-md-6 mb-3">
                <label for="client_national_num" class="form-label">رقم الهوية <span class="required">*</span></label>
                <input type="text" class="form-control" id="client_national_num" name="client_national_num" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="client_birthdate" class="form-label">تاريخ الميلاد <span class="required">*</span></label>
                <input type="date" class="form-control" id="client_birthdate" name="client_birthdate" required>
            </div>

            <!-- معلومات العمل والدخل (خاصة بعقود التأجير) -->
            <div class="col-md-6 mb-3">
                <label for="profession" class="form-label">المهنة <span class="required">*</span></label>
                <input type="text" class="form-control" id="profession" name="profession" placeholder="مثال: مهندس، طبيب، موظف..." required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="monthly_income" class="form-label">الدخل الشهري (ريال) <span class="required">*</span></label>
                <input type="number" class="form-control" id="monthly_income" name="monthly_income" min="0" step="0.01" placeholder="0.00" required>
            </div>

            <!-- معلومات السكن والعائلة -->
            <div class="col-md-12 mb-3">
                <label for="client_address" class="form-label">العنوان <span class="required">*</span></label>
                <textarea class="form-control" id="client_address" name="client_address" rows="2" placeholder="العنوان بالتفصيل..." required></textarea>
            </div>
            <div class="col-md-4 mb-3">
                <label for="client_city" class="form-label">المدينة <span class="required">*</span></label>
                <input type="text" class="form-control" id="client_city" name="client_city" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="family_num" class="form-label">عدد أفراد الأسرة</label>
                <input type="number" class="form-control" id="family_num" name="family_num" min="1" max="20">
            </div>
            <div class="col-md-4 mb-3">
                <label for="children" class="form-label">عدد الأطفال</label>
                <input type="number" class="form-control" id="children" name="children" min="0" max="15">
            </div>
        </div>

        <!-- ملخص معلومات العميل -->
        <div class="contract-summary mt-3">
            <h6 class="text-primary mb-2"><i class="fas fa-info-circle me-2"></i>ملخص معلومات العميل</h6>
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">الاسم:</small> <span id="client_summary_name">-</span><br>
                    <small class="text-muted">الجوال:</small> <span id="client_summary_phone">-</span><br>
                    <small class="text-muted">المهنة:</small> <span id="client_summary_profession">-</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">المدينة:</small> <span id="client_summary_city">-</span><br>
                    <small class="text-muted">الدخل الشهري:</small> <span id="client_summary_income">-</span><br>
                    <small class="text-muted">عدد الأفراد:</small> <span id="client_summary_family">-</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update client summary when fields change
$(document).ready(function() {
    $('#client_name').on('input', function() {
        $('#client_summary_name').text($(this).val() || '-');
    });

    $('#client_phone').on('input', function() {
        $('#client_summary_phone').text($(this).val() || '-');
    });

    $('#profession').on('input', function() {
        $('#client_summary_profession').text($(this).val() || '-');
    });

    $('#client_city').on('input', function() {
        $('#client_summary_city').text($(this).val() || '-');
    });

    $('#monthly_income').on('input', function() {
        const value = $(this).val();
        $('#client_summary_income').text(value ? parseFloat(value).toLocaleString() + ' ريال' : '-');
    });

    $('#family_num').on('input', function() {
        $('#client_summary_family').text($(this).val() || '-');
    });
});
</script>
