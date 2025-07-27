@extends('admin.layout.indexs.index')

@section('title', 'اختيار عاملة للعقد')

@section('style')
<style>
.maid-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    border-radius: 10px;
}

.maid-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.maid-card.shadow-lg {
    box-shadow: 0 12px 24px rgba(0,0,0,0.15) !important;
}

.maid-card.selected {
    border-color: #28a745;
    background-color: #f8fff9;
    box-shadow: 0 4px 8px rgba(40,167,69,0.3);
}

.card-header.bg-primary {
    border-radius: 10px 10px 0 0;
}

.card-footer.bg-light {
    border-radius: 0 0 10px 10px;
}

.maid-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #dee2e6;
}

.maid-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
}

.maid-status.available {
    background-color: #28a745;
    color: white;
}

.maid-status.reserved {
    background-color: #dc3545;
    color: white;
}

.select-maid-btn {
    transition: all 0.3s ease;
}

.select-maid-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.alert-info {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    color: white;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-1px);
}

.btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #0f8a7d 0%, #2edf6d 100%);
    transform: translateY(-1px);
}

.pagination {
    justify-content: center;
    margin-top: 2rem;
}
.pagination .page-item .page-link {
    color: #007bff;
    border-radius: 6px;
    margin: 0 2px;
    font-size: 1rem;
    min-width: 36px;
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.pagination .page-item.active .page-link {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}
.pagination .page-item.disabled .page-link {
    color: #ccc;
    background: #fff;
}
.pagination .page-link:focus {
    box-shadow: none;
}

/* إزالة أيقونات أو أسهم كبيرة غير مرغوبة */
.pagination svg,
.pagination i.fa,
.pagination .fa {
    font-size: 1rem !important;
    width: auto !important;
    height: auto !important;
    vertical-align: middle;
}

.pagination svg {
    width: 1em !important;
    height: 1em !important;
    display: inline !important;
    vertical-align: middle !important;
    stroke-width: 2 !important;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-plus me-2"></i>
                        اختيار عاملة للعقد
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('RecruitmentContract.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            العودة للعقود
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    <!-- عرض الرسائل -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif


                    <!-- Filters Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-filter me-2"></i>
                                فلاتر البحث
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="maidFilterForm" action="{{ route('RecruitmentContract.selectMaid', $contract->id) }}" method="GET">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="search" class="form-label">البحث بالاسم أو رقم الجواز</label>
                                            <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="ابحث عن عاملة...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="nationality_id" class="form-label">الجنسية</label>
                                            <select name="nationality_id" id="nationality_id" class="form-control select2">
                                                <option value="">الكل</option>
                                                @foreach($nationalities as $nationality)
                                                    <option value="{{ $nationality->id }}" {{ request('nationality_id') == $nationality->id ? 'selected' : '' }}>
                                                        {{ $nationality->sub_title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="religion_id" class="form-label">الديانة</label>
                                            <select name="religion_id" id="religion_id" class="form-control select2">
                                                <option value="">الكل</option>
                                                @foreach($religions as $religion)
                                                    <option value="{{ $religion->id }}" {{ request('religion_id') == $religion->id ? 'selected' : '' }}>
                                                        {{ $religion->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="job_title_id" class="form-label">المهنة</label>
                                            <select name="job_title_id" id="job_title_id" class="form-control select2">
                                                <option value="">الكل</option>
                                                @foreach($jobTitles as $jobTitle)
                                                    <option value="{{ $jobTitle->id }}" {{ request('job_title_id') == $jobTitle->id ? 'selected' : '' }}>
                                                        {{ $jobTitle->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>
                                            بحث
                                        </button>
                                        <a href="{{ route('RecruitmentContract.selectMaid', $contract->id) }}" class="btn btn-secondary">
                                            <i class="fas fa-refresh me-1"></i>
                                            إعادة تعيين
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">جاري التحميل...</span>
                        </div>
                        <p class="mt-2">جاري تحميل العاملات...</p>
                    </div>



                    <!-- Maids Grid -->
                    <div class="row" id="maidsGrid">
                        <div id="maidsGridWrapper">
                            @include('admin.ingaz.RecruitmentContract._maids_grid', ['availableMaids' => $availableMaids, 'contract' => $contract])
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($availableMaids->hasPages())
                        <div class="row mt-4">
                            <div class="col-12">
                                <nav aria-label="صفحات العاملات">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            عرض {{ $availableMaids->firstItem() ?? 0 }} إلى {{ $availableMaids->lastItem() ?? 0 }} من {{ $availableMaids->total() }} عاملة
                                        </div>
                                        <div>
                                            {{ $availableMaids->appends(request()->query())->links('pagination::bootstrap-4') }}
                                        </div>
                                    </div>
                                </nav>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmSelectionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-check me-2"></i>
                    تأكيد اختيار العاملة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-question-circle fa-3x text-primary"></i>
                </div>
                <h6>هل أنت متأكد من اختيار العاملة</h6>
                <p class="text-primary font-weight-bold" id="selectedMaidName"></p>
                <p class="text-muted">سيتم إضافة العاملة إلى العقد وتحديث حالة العقد</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    إلغاء
                </button>
                <button type="button" class="btn btn-success" id="confirmSelectionBtn">
                    <i class="fas fa-check me-1"></i>
                    تأكيد الاختيار
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2();

    var selectedMaidId = null;
    var selectedMaidName = null;

    // Handle maid selection
    $(document).on('click', '.select-maid-btn', function() {
        selectedMaidId = $(this).data('maid-id');
        selectedMaidName = $(this).data('maid-name');

        $('#selectedMaidName').text(selectedMaidName);
        $('#confirmSelectionModal').modal('show');
    });

    // Add hover effect to maid cards
    $('.maid-card').hover(
        function() {
            $(this).addClass('shadow-lg');
        },
        function() {
            $(this).removeClass('shadow-lg');
        }
    );

    // Add click effect to select buttons
    $('.select-maid-btn').on('click', function() {
        // Add visual feedback
        $(this).addClass('btn-success').removeClass('btn-outline-success');
        setTimeout(() => {
            $(this).removeClass('btn-success').addClass('btn-outline-success');
        }, 200);
    });

    // Show success message when page loads with success parameter
    @if(request('success'))
        Swal.fire({
            icon: 'success',
            title: 'تم بنجاح',
            text: 'تم تحديث العقد بنجاح',
            confirmButtonText: 'حسناً'
        });
    @endif

    // Handle confirmation
    $('#confirmSelectionBtn').on('click', function() {
        if (!selectedMaidId) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'يرجى اختيار عاملة أولاً',
                confirmButtonText: 'حسناً'
            });
            return;
        }

        var button = $(this);
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>جاري الإضافة...');

        $.ajax({
            url: '{{ route("RecruitmentContract.addMaid", $contract->id) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                maid_id: selectedMaidId
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: response.message,
                        confirmButtonText: 'حسناً'
                    }).then((result) => {
                        window.location.href = '{{ route("RecruitmentContract.index") }}';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: response.message,
                        confirmButtonText: 'حسناً'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                let errorMessage = 'حدث خطأ أثناء إضافة العاملة';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: errorMessage,
                    confirmButtonText: 'حسناً'
                });
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-check me-1"></i>تأكيد الاختيار');
            }
        });
    });

    // Reset modal when hidden
    $('#confirmSelectionModal').on('hidden.bs.modal', function () {
        selectedMaidId = null;
        selectedMaidName = null;
    });

    // AJAX filter/search
    function ajaxLoadMaids(url, data) {
        $('#maidsGridWrapper').fadeTo(200, 0.5);
        $.get(url, data, function(response) {
            var newContent = $(response).find('#maidsGridWrapper').html();
            $('#maidsGridWrapper').html(newContent).fadeTo(200, 1);
        });
    }

    // عند الكتابة أو تغيير الفلاتر
    $('#maidFilterForm').on('input change', 'input, select', function(e) {
        var form = $('#maidFilterForm');
        var url = form.attr('action');
        var data = form.serialize();
        ajaxLoadMaids(url, data);
        e.preventDefault();
    });

    // عند الضغط على pagination
    $(document).on('click', '#maidsGridWrapper .pagination a', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        ajaxLoadMaids(url);
    });

    // عطل submit الافتراضي للفورم
    $(document).on('submit', '#maidFilterForm', function(e) {
        e.preventDefault();
        var url = $(this).attr('action');
        var data = $(this).serialize();
        ajaxLoadMaids(url, data);
    });
});
</script>
@endsection
