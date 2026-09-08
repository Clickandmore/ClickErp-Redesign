@extends('admin.layout.indexs.index')

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">
                        <i class="fas fa-eye text-primary me-2"></i>
                        تفاصيل طلب نقل الخدمات
                    </h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">لوحة التحكم</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('SponsorshipTransfer.index') }}">إدارة نقل الخدمات</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('SponsorshipTransfer.webRequests') }}">طلبات
                                الموقع الخارجي</a></li>
                        <li class="breadcrumb-item active">تفاصيل الطلب</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="{{ route('SponsorshipTransfer.webRequests') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        <!-- Order Information -->
        <div id="order-content" class="row">
            <div class="col-lg-8">
                <!-- Order Details Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            معلومات الطلب
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="fw-bold text-muted">رقم الطلب:</label>
                                    <p class="mb-0">{{ $order->order_code }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="fw-bold text-muted">تاريخ الطلب:</label>
                                    <p class="mb-0">{{ $order->created_at->format('Y-m-d H:i') }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="fw-bold text-muted">آخر تحديث:</label>
                                    <p class="mb-0">{{ $order->updated_at->format('Y-m-d H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="fw-bold text-muted">حالة الطلب:</label>
                                    <p class="mb-0">
                                        @php
                                            $statusBadges = [
                                                'new' => 'badge-warning',
                                                'paid' => 'badge-info',
                                                'completed' => 'badge-success',
                                                'started' => 'badge-primary',
                                                'done' => 'badge-success',
                                                'refused' => 'badge-danger',
                                                'canceled' => 'badge-secondary'
                                            ];
                                            $badgeClass = $statusBadges[$order->status] ?? 'badge-secondary';
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $order->status }}</span>
                                    </p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="fw-bold text-muted">طريقة الدفع:</label>
                                    <p class="mb-0">{{ $order->payment_method }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client Information Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>
                            معلومات العميل
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($order->user)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">اسم العميل:</label>
                                        <p class="mb-0">{{ $order->user->name }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">رقم الجوال:</label>
                                        <p class="mb-0">{{ $order->user->phone }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">البريد الإلكتروني:</label>
                                        <p class="mb-0">{{ $order->user->email ?? 'غير محدد' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">{{ $order->user?->documentNumberLabel() ?? 'رقم الهوية' }}:</label>
                                        <p class="mb-0">{{ $order->user?->documentNumber() ?? 'غير محدد' }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">العنوان:</label>
                                        <p class="mb-0">{{ $order->user->address ?? 'غير محدد' }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">المدينة:</label>
                                        <p class="mb-0">{{ $order->user->city ?? 'غير محدد' }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                لا توجد معلومات العميل
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Maid Information Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-tie me-2"></i>
                            معلومات العاملة
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($order->maid)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">اسم العاملة:</label>
                                        <p class="mb-0">{{ $order->maid->name }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">رقم الجواز:</label>
                                        <p class="mb-0">{{ $order->maid->passport_num }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">الراتب:</label>
                                        <p class="mb-0">{{ $order->maid->salary }} ريال</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">الجنسية:</label>
                                        <p class="mb-0">{{ $order->maid->nationality ? $order->maid->nationality->sub_title : 'غير محدد' }}</p>
                                    </div>
                                    @if($order->maid->experience_status == 'has_experience')

                                        <div class="info-item mb-3">
                                            <label class="fw-bold text-muted">سنوات الخبرة:</label>
                                            <p class="mb-0">{{ $order->maid->experience_years }}</p>

                                        </div>
                                    @endif
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">حالة الخبرة:</label>
                                        @if($order->maid->experience_status == 'has_experience')
                                            <p class="mb-0">سبق لها العمل</p>
                                        @else
                                            <p class="mb-0">لم يسبق لها العمل</p>
                                            @endif
                                            </p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="fw-bold text-muted">الحالة:</label>
                                        <p class="mb-0">
                                            @php
                                                $maidStatusBadges = [
                                                    'available' => 'badge-success',
                                                    'reserved' => 'badge-warning',
                                                    'contracted' => 'badge-info',
                                                    'completed' => 'badge-secondary'
                                                ];
                                                $maidBadgeClass = $maidStatusBadges[$order->maid->status] ?? 'badge-secondary';
                                            @endphp
                                            <span class="badge {{ $maidBadgeClass }}">{{ $order->maid->status }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                لا توجد معلومات العاملة
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Customer Service Representative Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-headset me-2"></i>
                            ممثل خدمة العملاء
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $responsibleAdmin = $order->resolveResponsibleAdmin();
                        @endphp
                        @if($responsibleAdmin)
                            <div class="text-center mb-3">
                                <div class="avatar-circle mx-auto mb-3">
                                    <i class="fas fa-user fa-2x text-muted"></i>
                                </div>
                                <h6 class="mb-1">{{ $responsibleAdmin->name }}</h6>
                                <p class="text-muted mb-0">ممثل خدمة العملاء</p>
                            </div>
                            <div class="info-item mb-2">
                                <label class="fw-bold text-muted">رقم الاتصال:</label>
                                <p class="mb-0">{{ $responsibleAdmin->contact_phone ?? 'غير محدد' }}</p>
                            </div>
                            <div class="info-item mb-2">
                                <label class="fw-bold text-muted">رقم الواتساب:</label>
                                <p class="mb-0">{{ $responsibleAdmin->whatsapp_phone ?? 'غير محدد' }}</p>
                            </div>
                            <div class="info-item mb-2">
                                <label class="fw-bold text-muted">رقم التحقق:</label>
                                <p class="mb-0">{{ $responsibleAdmin->verification_phone ?? 'غير محدد' }}</p>
                            </div>
                            <div class="info-item mb-2">
                                <label class="fw-bold text-muted">البريد الإلكتروني:</label>
                                <p class="mb-0">{{ $responsibleAdmin->email ?? 'غير محدد' }}</p>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                لم يتم تعيين ممثل خدمة العملاء
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="card shadow">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            الإجراءات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($order->status == 'new')
                                <button class="btn btn-primary"
                                        onclick="updateOrderStatus('{{ $order->id }}', 'started')">
                                    <i class="fas fa-play me-2"></i>
                                    بدء المعالجة
                                </button>
                            @endif

                            @if($order->status == 'new' || $order->status == 'paid')
                                <a href="{{ route('SponsorshipTransfer.create', ['order_id' => $order->id]) }}"
                                   class="btn btn-success">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    إنشاء طلب نقل خدمات
                                </a>
                            @endif


                            <button class="btn btn-warning" onclick="exportOrder()">
                                <i class="fas fa-download me-2"></i>
                                تصدير PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
    <style>
        .info-item label {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .info-item p {
            font-size: 1rem;
            color: #333;
        }

        .avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #dee2e6;
        }
    </style>
@endpush
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

@push('js')
    <script>
        function updateOrderStatus(orderId, status) {
            var url = '{{ route("orders.changeStatus","orderId") }}'.replace('orderId', orderId)
            Swal.fire({
                title: 'تأكيد الإجراء',
                text: 'هل تريد تحديث حالة الطلب؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            order_id: orderId,
                            status: status,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'تم التحديث بنجاح',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('خطأ!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            Swal.fire('خطأ!', 'حدث خطأ أثناء تحديث الحالة', 'error');
                        }
                    });
                }
            });
        }



        function exportOrder() {
            const { jsPDF } = window.jspdf;

            html2canvas(document.getElementById("order-content")).then(canvas => {
                let imgData = canvas.toDataURL("image/png");
                let pdf = new jsPDF("p", "mm", "a4");

                let imgWidth = 210; // عرض صفحة A4 بالملم
                let pageHeight = 297;
                let imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;

                pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                while (heightLeft > 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                pdf.save("order.pdf"); // اسم الملف الناتج
            });
        }

    </script>
@endpush
