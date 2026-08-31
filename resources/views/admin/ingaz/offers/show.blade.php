@if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_VIEW, \App\PermKeys::OFFERS_CONTROL]))
    <div class="row g-3">
        <div class="col-md-4">
            <img src="{{ showFile($obj->image) }}" alt="{{ $obj->title }}" class="img-fluid rounded border">
        </div>
        <div class="col-md-8">
            <h5>{{ $obj->title }}</h5>
            <p class="text-muted">{{ $obj->description }}</p>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>نوع العرض</th>
                            <td>{{ $obj->type_label }}</td>
                        </tr>
                        <tr>
                            <th>السعر قبل الخصم</th>
                            <td>{{ $obj->price_before_discount ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>السعر بعد الخصم</th>
                            <td>{{ $obj->price_after_discount ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>نسبة الخصم</th>
                            <td>{{ $obj->discount_percentage !== null ? $obj->discount_percentage . '%' : '-' }}</td>
                        </tr>
                        <tr>
                            <th>الرابط</th>
                            <td>
                                @if($obj->url)
                                    <a href="{{ $obj->url }}" target="_blank" rel="noopener">{{ $obj->url }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>الفترة</th>
                            <td>{{ optional($obj->starts_at)->format('Y-m-d') ?? '-' }} / {{ optional($obj->ends_at)->format('Y-m-d') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>الحالة</th>
                            <td>{{ $obj->is_active ? 'نشط' : 'غير نشط' }}</td>
                        </tr>
                        <tr>
                            <th>العرض الأفضل</th>
                            <td>{{ $obj->is_featured ? 'نعم' : 'لا' }}</td>
                        </tr>
                        <tr>
                            <th>الترتيب</th>
                            <td>{{ $obj->sort_order }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if($obj->nationalities->isNotEmpty())
            <div class="col-12">
                <h6>الجنسيات المرتبطة</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($obj->nationalities as $nationality)
                        <span class="badge bg-light text-dark border">{{ $nationality->sub_title ?: $nationality->title ?: $nationality->id }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if($obj->maids->isNotEmpty())
            <div class="col-12">
                <h6>السير الذاتية المرتبطة</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>رقم السيرة</th>
                                <th>الجنسية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($obj->maids as $maid)
                                <tr>
                                    <td>{{ $maid->id }}</td>
                                    <td>{{ $maid->name }}</td>
                                    <td>{{ $maid->cv_code ?: '-' }}</td>
                                    <td>{{ optional($maid->nationality)->sub_title ?: optional($maid->nationality)->title ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@else
    @include('components.no_permissions')
@endif
