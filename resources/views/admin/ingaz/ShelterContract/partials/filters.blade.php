<div class="filter-section">
    <h5>فلتر البيانات</h5>
    <div class="row filter-row">
        <div class="col-md-2">
            <label for="filterMaidName" class="form-label">اسم العاملة</label>
            <input type="text" id="filterMaidName" class="form-control" placeholder="ابحث برقم العاملة">
        </div>
        <div class="col-md-2">
            <label for="filterPassport" class="form-label">رقم الجواز</label>
            <input type="text" id="filterPassport" class="form-control" placeholder="ابحث برقم الجواز">
        </div>
        <div class="col-md-2">
            <label for="filterMaidType" class="form-label">نوع العاملة</label>
            <select id="filterMaidType" class="form-select">
                <option value="">الكل</option>
                @foreach($jobTypes as $type)
                    @php
                        $locale = app()->getLocale();
                        $translation = $type->translations->where('locale', $locale)->first();
                        $title = $translation ? $translation->title : $type->title;
                    @endphp
                    <option value="{{ $type->id }}">{{ $title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="filterNationality" class="form-label">الجنسية</label>
            <select id="filterNationality" class="form-select">
                <option value="">الكل</option>
                @foreach($nationalities as $nationality)
                    @php
                        $locale = app()->getLocale();
                        $translation = $nationality->translations->where('locale', $locale)->first();
                        $name = $translation ? $translation->sub_title : $nationality->title ?? 'غير محدد';
                    @endphp
                    <option value="{{ $nationality->id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="filterShelter" class="form-label">مكان الإيواء</label>
            <select id="filterShelter" class="form-select">
                <option value="">الكل</option>
                @foreach($shelterPlaces as $shelter)
                    @php
                        $locale = app()->getLocale();
                        $translation = $shelter->translations->where('locale', $locale)->first();
                        $name = $translation ? $translation->title : $shelter->name ?? 'غير محدد';
                    @endphp
                    <option value="{{ $shelter->id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="filterReceiptSource" class="form-label">مصدر الاستلام</label>
            <select id="filterReceiptSource" class="form-select">
                <option value="">الكل</option>
                @foreach(__('shelter_worker_statuses.received_by') ?? [] as $k => $name)
                    <option value="{{ $k }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row filter-row mt-2">
        <div class="col-md-2">
            <label for="filterStatus" class="form-label">حالة الضمان</label>
            <select id="filterStatus" class="form-select">
                <option value="">الكل</option>
                <option value="within_guarantee">ضمن الضمان</option>
                <option value="expired_guarantee">منتهي الضمان</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="filterArrivalDate" class="form-label">تاريخ الوصول</label>
            <input type="date" id="filterArrivalDate" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="filterReturnDate" class="form-label">تاريخ العودة</label>
            <input type="date" id="filterReturnDate" class="form-control">
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <button type="button" id="clearFilters" class="btn btn-secondary me-2">
                <i class="fas fa-times"></i> مسح الفلاتر
            </button>
            <button type="button" id="exportData" class="btn btn-success">
                <i class="fas fa-download"></i> تصدير البيانات
            </button>
        </div>
    </div>
</div>
