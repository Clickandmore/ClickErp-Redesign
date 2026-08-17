<!-- Modal -->
<div class="modal fade" id="selectEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="selectEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectEmployeeModalLabel">اختيار الموظف المسؤول</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="employee_id">اختر الموظف المسؤول عن الطلب</label>
                    <select name="employee_id" id="employee_id" class="form-control select2" required>
                        <option value="">اختر الموظف المسؤول...</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="confirmSelection">تأكيد الاختيار</button>
            </div>
        </div>
    </div>
</div>

<style>
.select2-container {
    width: 100% !important;
}
.modal-lg {
    max-width: 800px;
}
</style>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('#employee_id').select2({
        placeholder: "اختر الموظف المسؤول...",
        allowClear: true
    });

    // Handle confirmation
    $('#confirmSelection').on('click', function() {
        try {
            var employeeId = $('#employee_id').val();
            if (!employeeId) {
                alert('الرجاء اختيار الموظف المسؤول');
                return;
            }
            $('#selectEmployeeForm').submit();
        } catch (error) {
            console.error('Error submitting form:', error);
            alert('حدث خطأ أثناء إرسال النموذج. يرجى المحاولة مرة أخرى.');
        }
    });
});
</script>
@endpush
