<!-- معلومات العاملة -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-female me-2"></i>معلومات العاملة</h5>
    </div>
    <div class="card-body">
        <!-- عرض معلومات العاملة المختارة -->
        <div id="selected-maid-info" style="display: none;">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="maid-photo-container mb-3">
                        <img id="maid_photo" src="" alt="صورة العاملة" class="img-thumbnail" style="width: 120px; height: 150px; object-fit: cover;">
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>الاسم:</strong> <span id="maid_name_display">-</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>رقم الجواز:</strong> <span id="maid_passport_display">-</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>الجنسية:</strong> <span id="maid_nationality_display">-</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>الديانة:</strong> <span id="maid_religion_display">-</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>العمر:</strong> <span id="maid_age_display">-</span> سنة
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>سنوات الخبرة:</strong> <span id="maid_experience_display">-</span> سنة
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>الراتب المطلوب:</strong> <span id="maid_salary_display">-</span> ريال
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>الحالة:</strong> <span id="maid_status_display" class="badge bg-success">متاحة للتأجير</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- مهارات العاملة -->
            <div class="mt-3">
                <h6 class="text-primary"><i class="fas fa-star me-2"></i>المهارات والخبرات</h6>
                <div id="maid_skills_display" class="d-flex flex-wrap gap-2">
                    <!-- سيتم تعبئتها من JavaScript -->
                </div>
            </div>
        </div>

        <!-- رسالة عدم اختيار عاملة -->
        <div id="no-maid-selected" class="text-center py-4">
            <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">لم يتم اختيار عاملة بعد</h5>
            <p class="text-muted">يرجى اختيار عاملة من القائمة أعلاه</p>
        </div>

        <!-- معلومات إضافية للعاملة في العقد الجديد -->
        <div id="new-maid-info" style="display: none;">
            <div class="contract-summary mt-3">
                <h6 class="text-primary mb-2"><i class="fas fa-info-circle me-2"></i>ملخص معلومات العاملة</h6>
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">الاسم:</small> <span id="new_maid_name_display">-</span><br>
                        <small class="text-muted">رقم الجواز:</small> <span id="new_maid_passport_display">-</span><br>
                        <small class="text-muted">الجنسية:</small> <span id="new_maid_nationality_display">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">العمر:</small> <span id="new_maid_age_display">-</span> سنة<br>
                        <small class="text-muted">حالة الخبرة:</small> <span id="new_maid_experience_status_display">-</span><br>
                        <small class="text-muted">الخبرة:</small> <span id="new_maid_experience_display">-</span> سنة<br>
                        <small class="text-muted">الراتب:</small> <span id="new_maid_salary_display">-</span> ريال
                    </div>
                </div>
            </div>
        </div>

        <!-- تنبيهات هامة -->
        <div class="alert alert-info mt-3">
            <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>معلومات هامة</h6>
            <ul class="mb-0">
                <li>العاملات المعروضة هنا متاحة للتأجير فقط</li>
                <li>يجب التأكد من صحة بيانات العاملة قبل إتمام العقد</li>
                <li>سيتم تحديث حالة العاملة إلى "متعاقد عليها" بعد حفظ العقد</li>
            </ul>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle order selection - populate maid data
    $('#order_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            showMaidInfo(selectedOption);
        } else {
            hideMaidInfo();
        }
    });

    // Handle new maid selection
    $('#new_maid_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            showNewMaidInfo(selectedOption);
        } else {
            hideNewMaidInfo();
        }
    });

    function showMaidInfo(option) {
        // Hide no selection message
        $('#no-maid-selected').hide();

        // Show maid info
        $('#selected-maid-info').show();

        // Populate maid data
        $('#maid_name_display').text(option.data('maid-name') || '-');
        $('#maid_passport_display').text(option.data('maid-passport') || '-');

        // You would load additional maid details via AJAX here
        loadMaidDetails(option.data('maid-id'));
    }

    function showNewMaidInfo(option) {
        $('#no-maid-selected').hide();
        $('#new-maid-info').show();

        $('#new_maid_name_display').text(option.data('name') || '-');
        $('#new_maid_passport_display').text(option.data('passport') || '-');
        $('#new_maid_age_display').text(option.data('age') || '-');
        $('#new_maid_experience_display').text(option.data('experience') || '-');

        const salary = option.data('salary');
        $('#new_maid_salary_display').text(salary ? parseFloat(salary).toLocaleString() : '-');

        // Load additional details
        loadMaidDetails(option.val());
    }

    function hideMaidInfo() {
        $('#selected-maid-info').hide();
        $('#no-maid-selected').show();
    }

    function hideNewMaidInfo() {
        $('#new-maid-info').hide();
        $('#no-maid-selected').show();
    }

    function loadMaidDetails(maidId) {
        if (!maidId) return;

        // AJAX call to load additional maid details
        $.ajax({
            url: '/admin/maids/' + maidId + '/details',
            method: 'GET',
            success: function(response) {
                if (response.success && response.maid) {
                    const maid = response.maid;

                    // Update photo
                    $('#maid_photo').attr('src', maid.photo_url || '/images/no-image.png');

                    // Update nationality and religion
                    $('#maid_nationality_display').text(maid.nationality_name || '-');
                    $('#maid_religion_display').text(maid.religion_name || '-');

                    // Update skills
                    if (maid.skills && maid.skills.length > 0) {
                        const skillsHtml = maid.skills.map(skill =>
                            `<span class="badge bg-primary">${skill}</span>`
                        ).join('');
                        $('#maid_skills_display').html(skillsHtml);
                    } else {
                        $('#maid_skills_display').html('<span class="text-muted">لا توجد مهارات محددة</span>');
                    }
                }
            },
            error: function() {
                console.log('Error loading maid details');
            }
        });
    }
});
</script>
