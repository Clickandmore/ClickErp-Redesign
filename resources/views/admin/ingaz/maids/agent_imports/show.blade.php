@extends('admin.layouts.app')

@section('title', 'مراجعة ملف العمالة #' . $import->id)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">مراجعة ملف العمالة #{{ $import->id }}</h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.agent-imports.download', $import) }}" class="btn btn-success">
                <i class="fa fa-download"></i> تحميل الملف الأصلي
            </a>
            <a href="{{ route('admin.agent-imports.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="fs-2 fw-bold">{{ $import->total_rows }}</div>
                    <div class="text-muted small">إجمالي السجلات</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success">{{ $import->valid_rows }}</div>
                    <div class="text-muted small">سجلات صحيحة</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-danger">{{ $import->invalid_rows }}</div>
                    <div class="text-muted small">بها أخطاء</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary">{{ $import->imported_rows }}</div>
                    <div class="text-muted small">تم استيرادها</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Info --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <strong>المكتب:</strong> {{ $import->office?->title ?? '-' }}
                </div>
                <div class="col-md-3">
                    <strong>نوع الملف:</strong>
                    <i class="fa {{ $import->file_type_icon }}"></i>
                    {{ strtoupper($import->file_type ?? '-') }}
                </div>
                <div class="col-md-3">
                    <strong>اسم الملف:</strong> {{ $import->original_filename }}
                </div>
                <div class="col-md-3">
                    <strong>تاريخ الرفع:</strong> {{ $import->created_at->format('Y-m-d H:i') }}
                </div>
                @if($import->approvedBy)
                <div class="col-md-3">
                    <strong>اعتمد بواسطة:</strong> {{ $import->approvedBy->name }}
                </div>
                @endif
                <div class="col-md-3">
                    @php
                        $badgeClass = match($import->status) {
                            'completed'      => 'success',
                            'rejected','failed' => 'danger',
                            'pending_review' => 'warning',
                            default          => 'secondary'
                        };
                    @endphp
                    <strong>الحالة:</strong>
                    <span class="badge bg-{{ $badgeClass }}">{{ $import->status_label }}</span>
                </div>
            </div>

            @if($import->status === 'rejected' && $import->rejection_reason)
                <div class="alert alert-danger mt-3 mb-0">
                    <strong>سبب الرفض:</strong> {{ $import->rejection_reason }}
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    @if($import->can_approve)
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-body d-flex align-items-center gap-3">
            <form method="POST" action="{{ route('admin.agent-imports.approve', $import) }}"
                  onsubmit="return confirm('هل أنت متأكد من اعتماد هذا الاستيراد؟')">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-check-circle"></i> اعتماد واستيراد السجلات
                </button>
            </form>
            <span class="text-muted small">سيتم إضافة العمالة الصحيحة فقط، بمكتب الوكيل، بحالة "متاح" وغير مظهرة للموقع حتى المراجعة.</span>
        </div>
    </div>
    @endif

    @if(in_array($import->status, ['pending_review', 'processing']))
    <div class="card shadow-sm mb-4 border-danger">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.agent-imports.reject', $import) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold text-danger">رفض الاستيراد</label>
                    <textarea name="rejection_reason" class="form-control" rows="2"
                              placeholder="اكتب سبب الرفض ليراه الوكيل..." required></textarea>
                    @error('rejection_reason')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-danger"
                        onclick="return confirm('هل أنت متأكد من رفض هذا الاستيراد؟')">
                    <i class="fa fa-times-circle"></i> رفض الاستيراد
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Rows Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">السجلات المستخرجة</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center" style="font-size:13px;">
                    <thead class="table-secondary">
                        <tr>
                            <th>رقم الصف</th>
                            <th>الاسم</th>
                            <th>رقم الجواز</th>
                            <th>الجنسية</th>
                            <th>العمر</th>
                            <th>سنوات الخبرة</th>
                            <th>التكلفة</th>
                            <th>الحالة</th>
                            <th>الأخطاء</th>
                            <th>سجل في النظام</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($import->rows as $row)
                        @php
                            $data = $row->normalized_data ?? [];
                            $rowBadge = match($row->status) {
                                'pending'  => ['label' => 'بانتظار الاعتماد', 'class' => 'warning'],
                                'imported' => ['label' => 'تم الاستيراد', 'class' => 'success'],
                                'invalid'  => ['label' => 'بيانات خاطئة', 'class' => 'danger'],
                                'failed'   => ['label' => 'فشل الاستيراد', 'class' => 'danger'],
                                default    => ['label' => $row->status, 'class' => 'secondary'],
                            };
                        @endphp
                        <tr>
                            <td>{{ $row->row_number }}</td>
                            <td>{{ $data['name'] ?? '-' }}</td>
                            <td>{{ $data['passport_num'] ?? '-' }}</td>
                            <td>{{ $data['nationality_id'] ?? '-' }}</td>
                            <td>{{ $data['age'] ?? '-' }}</td>
                            <td>{{ $data['experience_years'] ?? '-' }}</td>
                            <td>{{ isset($data['price']) ? number_format($data['price'], 2) : '-' }}</td>
                            <td><span class="badge bg-{{ $rowBadge['class'] }}">{{ $rowBadge['label'] }}</span></td>
                            <td class="text-start">
                                @if(!empty($row->error_messages))
                                    <ul class="mb-0 ps-3 text-danger" style="font-size:11px;">
                                        @foreach($row->error_messages as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-success">✓</span>
                                @endif
                            </td>
                            <td>
                                @if($row->maid_id)
                                    <a href="{{ route('showMaidDetails', $row->maid_id) }}" class="btn btn-xs btn-outline-primary" style="font-size:11px;">
                                        #{{ $row->maid_id }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="py-4 text-muted">لا توجد سجلات.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
