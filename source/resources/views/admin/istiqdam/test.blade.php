@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
اختبار منصة الاستقدام
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">اختبار التكامل مع منصة الاستقدام</h4>
                    <p class="text-muted">اختبار جميع وظائف API منصة الاستقدام الحكومية</p>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-flask me-2"></i>
                            نتائج الاختبار
                        </h5>
                    </div>
                    <div class="card-body" id="testResults">
                        <div class="text-center">
                            <button class="btn btn-primary" id="startTestBtn">
                                <i class="fas fa-play me-2"></i>
                                بدء الاختبار
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section("js")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('#startTestBtn').on('click', function() {
        runTests();
    });

    async function runTests() {
        $('#testResults').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري الاختبار...</span></div><p class="mt-2">جاري تشغيل الاختبارات...</p></div>');

        const tests = [
            { name: 'اختبار الاتصال', url: '{{ route("istiqdam.test.connection") }}', method: 'GET' },
            { name: 'جلب الباقات', url: '{{ route("istiqdam.packages.data") }}?size=5', method: 'GET' },
        ];

        let results = '<div class="test-results">';

        for (let test of tests) {
            results += `<div class="test-item mb-3"><h6><i class="fas fa-cog fa-spin me-2"></i>${test.name}</h6>`;

            try {
                const response = await fetch(test.url, {
                    method: test.method,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    results += `<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>نجح: ${data.message || 'تم بنجاح'}</div>`;
                } else {
                    results += `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>تحذير: ${data.message || data.error || 'فشل الاختبار'}</div>`;
                }
            } catch (error) {
                results += `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>خطأ: ${error.message}</div>`;
            }

            results += '</div>';
        }

        results += '</div>';
        results += '<div class="text-center mt-4"><button class="btn btn-success" id="runAgainBtn"><i class="fas fa-redo me-2"></i>إعادة الاختبار</button></div>';

        $('#testResults').html(results);

        $('#runAgainBtn').on('click', function() {
            runTests();
        });
    }
});
</script>

<style>
.page-title-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.test-item {
    border-left: 4px solid #007bff;
    padding-left: 15px;
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}
</style>
@endsection
