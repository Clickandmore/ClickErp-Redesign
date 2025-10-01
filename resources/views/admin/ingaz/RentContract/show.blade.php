@extends('admin.layout.indexs.index')
@section('page-title')
    تفاصيل عقد التأجير - العقد رقم #{{ $contract->id }}
@endsection

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .contract-details-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .contract-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .contract-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .contract-header .contract-status {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
            font-weight: 600;
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #28a745;
        }

        .info-card.client-info {
            border-left-color: #007bff;
        }

        .info-card.maid-info {
            border-left-color: #ffc107;
        }

        .info-card.contract-info {
            border-left-color: #28a745;
        }

        .info-card.financial-info {
            border-left-color: #dc3545;
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-left: 10px;
            font-size: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }

        .info-value {
            color: #212529;
            text-align: right;
        }

        .info-value.highlight {
            font-weight: 600;
            color: #28a745;
        }

        .action-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }

        .btn-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
            color: white;
        }

        .btn-print {
            background: #17a2b8;
        }

        .btn-edit {
            background: #ffc107;
        }

        .btn-complete {
            background: #28a745;
        }

        .btn-back {
            background: #6c757d;
        }

        .btn-delete {
            background: #dc3545;
        }

        .financial-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .financial-item {
            text-align: center;
            padding: 15px;
        }

        .financial-amount {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .financial-label {
            font-size: 14px;
            opacity: 0.9;
        }

        @media print {
            .action-buttons {
                display: none !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
@endsection

@section('content')

    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW))

        <div class="contract-details-container" id="printableArea">
            <!-- Contract Header -->
            <div class="contract-header">
                <h2>عقد تأجير عاملة منزلية</h2>
                <div class="contract-status">
                    رقم العقد: {{ $contract->contract_number ?? $contract->id }}
                </div>
                <div class="contract-status">
                    الحالة: {{ $contract->status}}
                </div>
            </div>

            <!-- Financial Summary -->
            @if($contract->info)
                <div class="financial-summary">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="financial-item">
                                <div
                                    class="financial-amount">{{ number_format($contract->info->monthly_cost ?? 0, 2) }}</div>
                                <div class="financial-label">التكلفة الشهرية (ريال)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="financial-item">
                                <div class="financial-amount">{{ $contract->info->contract_duration_months ?? 0 }}</div>
                                <div class="financial-label">مدة العقد (شهر)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="financial-item">
                                <div
                                    class="financial-amount">{{ number_format($contract->info->contract_value ?? 0, 2) }}</div>
                                <div class="financial-label">قيمة العقد الإجمالية (ريال)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="financial-item">
                                <div class="financial-amount">{{ $contract->calculateRemainingDays() ?? 0 }}</div>
                                <div class="financial-label">الأيام المتبقية</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                <!-- معلومات العميل -->
                <div class="col-md-6 mb-4">
                    <div class="info-card client-info">
                        <div class="card-title">
                            <i class="fas fa-user text-primary"></i>
                            معلومات العميل
                        </div>
                        @if($contract->user)
                            <div class="info-row">
                                <span class="info-label">الاسم:</span>
                                <span class="info-value highlight">{{ $contract->user->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجوال:</span>
                                <span
                                    class="info-value">{{ $contract->user->phone_code }}{{ $contract->user->phone }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $contract->user->email ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الهوية:</span>
                                <span class="info-value">{{ $contract->user->national_num }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">تاريخ الميلاد:</span>
                                <span
                                    class="info-value">{{ $contract->user->birthdate ? $contract->user->birthdate : 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الجنسية:</span>
                                <span class="info-value">{{ $contract->user->nationality->name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">المهنة:</span>
                                <span class="info-value">{{ $contract->user->profession ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الدخل الشهري:</span>
                                <span
                                    class="info-value">{{ $contract->user->monthly_income ? number_format($contract->user->monthly_income, 2) . ' ريال' : 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">العنوان:</span>
                                <span class="info-value">{{ $contract->user->address }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">المدينة:</span>
                                <span class="info-value">{{ $contract->user->city }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">عدد أفراد الأسرة:</span>
                                <span class="info-value">{{ $contract->user->family_num ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">عدد الأطفال:</span>
                                <span class="info-value">{{ $contract->user->children ?? 'غير محدد' }}</span>
                            </div>
                        @else
                            <p class="text-muted">لا توجد معلومات للعميل</p>
                        @endif
                    </div>
                </div>

                <!-- معلومات العاملة -->
                <div class="col-md-6 mb-4">
                    <div class="info-card maid-info">
                        <div class="card-title">
                            <i class="fas fa-female text-warning"></i>
                            معلومات العاملة
                        </div>
                        @if($contract->maid)
                            <div class="text-center mb-3">
                                <img src="{{ $contract->maid->photo_url }}" alt="صورة العاملة"
                                     class="img-thumbnail" style="width: 100px; height: 120px; object-fit: cover;">
                            </div>
                            <div class="info-row">
                                <span class="info-label">الاسم:</span>
                                <span class="info-value highlight">{{ $contract->maid->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجواز:</span>
                                <span class="info-value">{{ $contract->maid->passport_num }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الجنسية:</span>
                                <span
                                    class="info-value">{{ optional($contract->maid->nationality->first())->title ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الديانة:</span>
                                <span
                                    class="info-value">{{ $contract->maid && $contract->maid->religion ? ($contract->maid->religion->title ?? $contract->maid->religion->first()->title ?? 'غير محدد') : 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">العمر:</span>
                                <span class="info-value">{{ $contract->maid->age }} سنة</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">سنوات الخبرة:</span>
                                <span class="info-value">{{ $contract->maid->experience_years }} سنة</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">حالة الخبرة:</span>
                                <span class="info-value">{{ $contract->maid->experience_status }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الراتب المطلوب:</span>
                                <span class="info-value">{{ number_format($contract->maid->salary, 2) }} ريال</span>
                            </div>
                        @else
                            <p class="text-muted">لم يتم تحديد عاملة بعد</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- معلومات المسوق -->
            @if($contract->marketer)
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="info-card">
                            <div class="card-title">
                                <i class="fas fa-user-tie text-primary"></i>
                                معلومات المسوق
                            </div>
                            <div class="info-row">
                                <span class="info-label">اسم المسوق:</span>
                                <span class="info-value highlight">{{ $contract->marketer->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجوال:</span>
                                <span class="info-value">{{ $contract->marketer->phone ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $contract->marketer->email ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- معلومات العقد -->
            @if($contract->info)
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="info-card contract-info">
                            <div class="card-title">
                                <i class="fas fa-file-contract text-success"></i>
                                تفاصيل عقد التأجير
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <span class="info-label">تاريخ العقد:</span>
                                        <span
                                            class="info-value">{{ $contract->contract_date ? $contract->contract_date->format('Y-m-d') : 'غير محدد' }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">ساعات العمل يومياً:</span>
                                        <span
                                            class="info-value">{{ $contract->info->working_hours_per_day }} ساعة</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">أجرة الساعة:</span>
                                        <span class="info-value">{{ number_format($contract->info->hourly_rate, 2) }} ريال</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">الراتب الشهري:</span>
                                        <span class="info-value">{{ number_format($contract->info->monthly_salary, 2) }} ريال</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">مبلغ نقل الكفالة:</span>
                                        <span class="info-value">{{ number_format($contract->info->sponsorship_transfer_amount, 2) }} ريال</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">الراتب بعد نقل الكفالة:</span>
                                        <span class="info-value">{{ number_format($contract->info->salary_after_sponsorship, 2) }} ريال</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <span class="info-label">التكلفة الشهرية:</span>
                                        <span class="info-value highlight">{{ number_format($contract->info->monthly_cost, 2) }} ريال</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">مدة العقد:</span>
                                        <span class="info-value">{{ $contract->info->contract_duration_months }} شهر ({{ $contract->info->contract_duration_days }} يوم)</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">قيمة العقد الإجمالية:</span>
                                        <span class="info-value highlight">{{ number_format($contract->info->contract_value, 2) }} ريال</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">الإنجاز من:</span>
                                        <span
                                            class="info-value">{{ $contract->info->ingaz_from ? $contract->info->ingaz_from->format('Y-m-d') : 'غير محدد' }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">الإنجاز إلى:</span>
                                        <span
                                            class="info-value">{{ $contract->info->ingaz_to ? $contract->info->ingaz_to->format('Y-m-d') : 'غير محدد' }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">حالة الضمان:</span>
                                        <span class="info-value">{{ $contract->warranty_status }}</span>
                                    </div>
                                </div>
                            </div>
                            @if($contract->info->notes)
                                <div class="mt-3">
                                    <strong>ملاحظات:</strong>
                                    <p class="mt-2 p-3 bg-light rounded">{{ $contract->info->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- معلومات إضافية -->
            <div class="row">
                <div class="col-12">
                    <div class="info-card">
                        <div class="card-title">
                            <i class="fas fa-info-circle text-info"></i>
                            معلومات إضافية
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <span class="info-label">تاريخ الإنشاء:</span>
                                    <span class="info-value">{{ $contract->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">آخر تحديث:</span>
                                    <span class="info-value">{{ $contract->updated_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if($contract->warranty_start_date)
                                    <div class="info-row">
                                        <span class="info-label">بداية الضمان:</span>
                                        <span
                                            class="info-value">{{ $contract->warranty_start_date->format('Y-m-d') }}</span>
                                    </div>
                                @endif
                                @if($contract->warranty_end_date)
                                    <div class="info-row">
                                        <span class="info-label">انتهاء الضمان:</span>
                                        <span
                                            class="info-value">{{ $contract->warranty_end_date->format('Y-m-d') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons no-print">
            <button type="button" class="btn-icon btn-print" onclick="printContract()" title="طباعة العقد">
                <i class="fas fa-print"></i>
            </button>

            @if(@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT))
                <a href="{{ route('RentContract.edit', $contract->id) }}" class="btn-icon btn-edit" title="تعديل العقد">
                    <i class="fas fa-edit"></i>
                </a>
            @endif

            @if($contract->status !== 'completed' && @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL))
                <button type="button" class="btn-icon btn-complete" onclick="completeContract()" title="إنهاء العقد">
                    <i class="fas fa-check"></i>
                </button>
            @endif

            <a href="{{ route('RentContract.index') }}" class="btn-icon btn-back" title="العودة للقائمة">
                <i class="fas fa-arrow-right"></i>
            </a>

            @if(@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_DELETE))
                <button type="button" class="btn-icon btn-delete" onclick="deleteContract()" title="حذف العقد">
                    <i class="fas fa-trash"></i>
                </button>
            @endif
        </div>

    @else
        @include('components.no_permissions')
    @endif

@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function printContract() {
            window.print();
        }

        function completeContract() {
            Swal.fire({
                title: 'تأكيد إنهاء العقد',
                text: 'هل أنت متأكد من إنهاء هذا العقد؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'نعم، إنهاء العقد',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('RentContract.complete', $contract->id) }}",
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'تم بنجاح',
                                    text: response.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: response.message
                                });
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: 'حدث خطأ أثناء إنهاء العقد'
                            });
                        }
                    });
                }
            });
        }

        function deleteContract() {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: 'هل أنت متأكد من حذف هذا العقد؟ لا يمكن التراجع عن هذا الإجراء.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، احذف العقد',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = $('<form>', {
                        'method': 'POST',
                        'action': "{{ route('RentContract.destroy', $contract->id) }}"
                    });
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': '{{ csrf_token() }}'
                    }));
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'DELETE'
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        }

        // Helper functions for status text (these would be methods in the controller)
        @php
            $statusTexts = [
                'new' => 'جديد',
                'active' => 'نشط',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغي',
                'suspended' => 'معلق',
            ];

            $warrantyTexts = [
                'not_applicable' => 'غير قابل للتطبيق',
                'active' => 'نشط',
                'expired' => 'منتهي',
            ];
        @endphp

// Add these as methods to the controller or pass them to the view
</script>
@endsection
