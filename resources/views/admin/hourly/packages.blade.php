@extends('admin.layout.indexs.index')
@section('page-title', 'باقات التأجير بالساعة')
@section('style')
    @include('admin.hourly_teams._style')
@endsection
@section('content')
<div class="container-fluid ht-page" dir="rtl">
    @include('admin.hourly.messages')
    <div class="ht-card has-publication">
        <div class="ht-head">
            <div class="ht-head-icon"><i class="fa fa-box-open"></i></div>
            <div>
                <h5>باقات التأجير بالساعة</h5>
                <p>ساعات الزيارة وعددها وسعرها، وظهور كل باقة في الموقع</p>
            </div>
            <div class="ht-actions">
                <a class="btn btn-outline-primary rounded-3" href="{{ route('admin.hourly-service-items.index') }}"><i class="fa fa-layer-group ms-1"></i> باقات الخدمات</a>
                @if (AdminGuardHasPerm('hourly_packages_create'))
                    <a class="btn btn-primary rounded-3" href="{{ route('admin.hourly-packages.create') }}"><i class="fa fa-plus ms-1"></i> إضافة باقة</a>
                @endif
            </div>
        </div>
        <div class="ht-body">
            @if ($packages->isEmpty())
                <div class="ht-empty"><i class="fa fa-box-open"></i> لا توجد باقات بعد.</div>
            @else
                <div class="table-responsive">
                    <table class="ht-table">
                        <thead>
                            <tr>
                                <th>الباقة</th>
                                <th class="text-center">ساعات الزيارة</th>
                                <th class="text-center">الزيارات</th>
                                <th>سعر الزيارة <small class="fw-normal">(شامل الضريبة)</small></th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packages as $p)
                                <tr>
                                    <td>
                                        <strong>{{ $p->title }}</strong>
                                        <span class="small text-muted ht-num ms-1" dir="ltr">{{ $p->code }}</span>
                                        <div class="mt-1">@include('admin.hourly._publication-badge', ['report' => $publication[$p->id], 'badgeId' => 'website-package-'.$p->id])</div>
                                    </td>
                                    <td class="text-center ht-num">{{ $p->hours_per_visit }}</td>
                                    <td class="text-center ht-num">{{ $p->visits_count }}</td>
                                    <td class="ht-num"><strong>{{ number_format((float) $p->price_per_visit, 2) }}</strong> <small class="text-muted">ر.س</small></td>
                                    <td><span class="ht-chip {{ $p->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">{{ $p->is_active ? 'مفعّلة' : 'متوقفة' }}</span></td>
                                    <td class="text-nowrap">
                                        <div class="d-inline-flex gap-1">
                                            @if (AdminGuardHasPerm('hourly_packages_edit'))
                                                <a class="btn btn-sm btn-outline-primary ht-icon-btn" href="{{ route('admin.hourly-packages.edit', $p) }}" title="تعديل" aria-label="تعديل"><i class="fa fa-pen"></i></a>
                                            @endif
                                            @if (AdminGuardHasPerm('hourly_packages_delete'))
                                                <form method="POST" action="{{ route('admin.hourly-packages.destroy', $p) }}"
                                                      data-confirm-title="أرشفة الباقة" data-confirm="أرشفة باقة {{ $p->title }}؟ لن تظهر للعملاء بعد الأرشفة." data-confirm-button="نعم، أرشفة">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger ht-icon-btn" title="أرشفة" aria-label="أرشفة"><i class="fa fa-box-archive"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $packages->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('js')
    @include('admin.hourly._confirm-script')
@endpush
