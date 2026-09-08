<!-- معلومات العقد -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>معلومات عقد التأجير</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- المسوق -->
            <div class="col-md-6 mb-3">
                <label for="marketer_id" class="form-label">المسوق <span class="required">*</span></label>
                <select class="form-select select2" id="marketer_id" name="marketer_id" required>
                    <option value="" disabled selected>اختر المسوق...</option>
                    @foreach($marketers as $marketer)
                        <option value="{{$marketer->id}}"
                            @if(auth()->guard('admin')->user()->id == $marketer->id) selected @endif>
                            {{$marketer->name}}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- تاريخ العقد -->
            <div class="col-md-6 mb-3">
                <label for="contract_date" class="form-label">تاريخ العقد <span class="required">*</span></label>
                <input type="date" class="form-control" id="contract_date" name="contract_date" value="{{date('Y-m-d')}}" required>
            </div>

            <!-- معلومات العمل والراتب -->
            <div class="col-md-4 mb-3">
                <label for="working_hours_per_day" class="form-label">ساعات العمل يومياً <span class="required">*</span></label>
                <input type="number" class="form-control" id="working_hours_per_day" name="working_hours_per_day" min="1" max="24" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="hourly_rate" class="form-label">أجرة الساعة (ريال) <span class="required">*</span></label>
                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="monthly_salary" class="form-label">الراتب الشهري (ريال) <span class="required">*</span></label>
                <input type="number" class="form-control" id="monthly_salary" name="monthly_salary" min="0" step="0.01" required>
            </div>

            <!-- معلومات نقل الخدمات -->
            <div class="col-md-6 mb-3">
                <label for="sponsorship_transfer_amount" class="form-label">مبلغ نقل الخدمات (ريال) <span class="required">*</span></label>
                <input type="number" class="form-control" id="sponsorship_transfer_amount" name="sponsorship_transfer_amount" min="0" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="salary_after_sponsorship" class="form-label">الراتب بعد نقل الخدمات (ريال) <span class="required">*</span></label>
                <input type="number" class="form-control" id="salary_after_sponsorship" name="salary_after_sponsorship" min="0" step="0.01" required>
            </div>

            <!-- التكلفة والمدة -->
            <div class="col-md-4 mb-3">
                <label for="monthly_cost" class="form-label">التكلفة الشهرية (ريال) <span class="required">*</span></label>
                <input type="number" class="form-control" id="monthly_cost" name="monthly_cost" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="contract_duration_months" class="form-label">مدة العقد (شهور) <span class="required">*</span></label>
                <input type="number" class="form-control" id="contract_duration_months" name="contract_duration_months" min="1" max="60" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="contract_duration_days" class="form-label">مدة العقد (أيام) <span class="required">*</span></label>
                <input type="number" class="form-control" id="contract_duration_days" name="contract_duration_days" min="1" required>
            </div>

            <!-- تواريخ الإنجاز -->
            <div class="col-md-6 mb-3">
                <label for="ingaz_from" class="form-label">الإنجاز من <span class="required">*</span></label>
                <input type="date" class="form-control" id="ingaz_from" name="ingaz_from" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">الإنجاز إلى</label>
                <div class="form-control-plaintext bg-light p-2 rounded" id="ingaz_to_display">
                    سيتم حسابه تلقائياً
                </div>
            </div>

            <!-- ملاحظات -->
            <div class="col-12 mb-3">
                <label for="notes" class="form-label">ملاحظات إضافية</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="أي ملاحظات أو شروط إضافية..."></textarea>
            </div>
        </div>

        <!-- ملخص العقد -->
        <div class="contract-summary mt-4">
            <h6 class="text-primary mb-3"><i class="fas fa-calculator me-2"></i>ملخص العقد المالي</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted mb-1">التكلفة الشهرية</h6>
                        <h5 class="text-primary mb-0" id="summary_monthly_cost">0 ريال</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted mb-1">مدة العقد</h6>
                        <h5 class="text-info mb-0" id="summary_duration">0 شهر</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted mb-1">قيمة العقد الإجمالية</h6>
                        <h5 class="text-success mb-0" id="summary_total_value">0 ريال</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-light rounded">
                        <h6 class="text-muted mb-1">الأجر اليومي</h6>
                        <h5 class="text-warning mb-0" id="summary_daily_rate">0 ريال</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- تنبيهات مهمة -->
        <div class="alert alert-warning mt-3">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>تنبيهات مهمة</h6>
            <ul class="mb-0">
                <li>قيمة العقد = التكلفة الشهرية × عدد الشهور</li>
                <li>تاريخ انتهاء الإنجاز = تاريخ البداية + عدد الأيام</li>
                <li>يجب التأكد من صحة جميع المبالغ المالية</li>
                <li>سيتم تطبيق شروط الضمان حسب النظام</li>
            </ul>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-calculate contract value
    function calculateContractValue() {
        const monthlyCost = parseFloat($('#monthly_cost').val()) || 0;
        const durationMonths = parseInt($('#contract_duration_months').val()) || 0;
        const totalValue = monthlyCost * durationMonths;

        $('#summary_monthly_cost').text(monthlyCost.toLocaleString() + ' ريال');
        $('#summary_duration').text(durationMonths + ' شهر');
        $('#summary_total_value').text(totalValue.toLocaleString() + ' ريال');

        // Calculate daily rate
        const workingHours = parseInt($('#working_hours_per_day').val()) || 8;
        const dailyRate = monthlyCost / 30; // Assuming 30 days per month
        $('#summary_daily_rate').text(dailyRate.toLocaleString() + ' ريال');
    }

    // Auto-calculate ingaz_to date
    function calculateIngazTo() {
        const ingazFrom = $('#ingaz_from').val();
        const durationDays = parseInt($('#contract_duration_days').val()) || 0;

        if (ingazFrom && durationDays) {
            const fromDate = new Date(ingazFrom);
            const toDate = new Date(fromDate.getTime() + (durationDays * 24 * 60 * 60 * 1000));
            const formattedDate = toDate.toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            $('#ingaz_to_display').text(formattedDate);
        } else {
            $('#ingaz_to_display').text('سيتم حسابه تلقائياً');
        }
    }

    // Auto-calculate monthly salary based on hourly rate
    function calculateMonthlySalary() {
        const hourlyRate = parseFloat($('#hourly_rate').val()) || 0;
        const workingHours = parseInt($('#working_hours_per_day').val()) || 0;

        if (hourlyRate && workingHours) {
            // Assuming 30 working days per month
            const monthlySalary = hourlyRate * workingHours * 30;
            $('#monthly_salary').val(monthlySalary.toFixed(2));
        }
    }

    // Event listeners
    $('#monthly_cost, #contract_duration_months').on('input', calculateContractValue);
    $('#ingaz_from, #contract_duration_days').on('change', calculateIngazTo);
    $('#hourly_rate, #working_hours_per_day').on('input', calculateMonthlySalary);

    // Set default marketer to current user if available
    @if(auth()->guard('admin')->check())
        $('#marketer_id').val('{{ auth()->guard("admin")->user()->id }}').trigger('change');
    @endif

    // Set default contract date to today
    if (!$('#contract_date').val()) {
        $('#contract_date').val('{{ date("Y-m-d") }}');
    }

    // Set default ingaz_from to contract date
    $('#contract_date').change(function() {
        if (!$('#ingaz_from').val()) {
            $('#ingaz_from').val($(this).val());
            calculateIngazTo();
        }
    });

    // Auto-calculate duration in days when months change
    $('#contract_duration_months').on('input', function() {
        const months = parseInt($(this).val()) || 0;
        const days = months * 30; // Approximate
        $('#contract_duration_days').val(days);
        calculateIngazTo();
    });

    // Initialize calculations
    calculateContractValue();
    calculateIngazTo();
});
</script>
