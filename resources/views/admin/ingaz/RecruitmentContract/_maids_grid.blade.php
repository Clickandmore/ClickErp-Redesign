{{-- ملخص النتائج --}}
@if($availableMaids->total() > 0)
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-light border">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-users me-2"></i>
                        تم العثور على <strong>{{ $availableMaids->total() }}</strong> عاملة متاحة
                    </div>
                    @if($availableMaids->hasPages())
                        <div class="text-muted">
                            الصفحة {{ $availableMaids->currentPage() }} من {{ $availableMaids->lastPage() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

{{-- شبكة العاملات --}}
<div class="row" id="maidsGrid">
    @forelse($availableMaids as $maid)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 maid-card shadow-sm" data-maid-id="{{ $maid->id }}">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-1"></i>
                        {{ $maid->name }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-2"><strong class="text-primary">رقم الجواز:</strong><br>{{ $maid->passport_num }}</p>
                            <p class="mb-2"><strong class="text-primary">الجنسية:</strong><br>{{ $maid->nationality->sub_title ?? 'غير محدد' }}</p>
                            <p class="mb-2"><strong class="text-primary">الديانة:</strong><br>{{ $maid->religion->title ?? 'غير محدد' }}</p>
                        </div>
                        <div class="col-6">
                            <p class="mb-2"><strong class="text-primary">المهنة:</strong><br>{{ $maid->jobTitles->first()->title ?? 'غير محدد' }}</p>
                            <p class="mb-2"><strong class="text-primary">الراتب:</strong><br>{{ $maid->salary ? $maid->salary . ' ريال' : 'غير محدد' }}</p>
                            <p class="mb-2"><strong class="text-primary">الخبرة:</strong><br>{{ $maid->experience_years ? $maid->experience_years . ' سنوات' : 'غير محدد' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p class="mb-2"><strong class="text-primary">العمر:</strong> {{ $maid->age ? $maid->age . ' سنة' : 'غير محدد' }}</p>
                            @if($maid->cv_file)
                                <a href="{{ asset('storage/' . $maid->cv_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file-pdf me-1"></i>
                                    عرض السيرة الذاتية
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <button type="button" class="btn btn-success btn-block select-maid-btn"
                            data-maid-id="{{ $maid->id }}"
                            data-maid-name="{{ $maid->name }}"
                            data-contract-id="{{ $contract->id }}">
                        <i class="fas fa-check me-1"></i>
                        اختيار هذه العاملة
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-search fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">لا توجد عاملات متاحة حالياً</h5>
                @if(request('search') || request('nationality_id') || request('religion_id') || request('job_title_id'))
                    <p class="text-muted">جرب تغيير الفلاتر أو إعادة تعيين البحث</p>
                    <a href="{{ route('RecruitmentContract.selectMaid', $contract->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-refresh me-1"></i>
                        إعادة تعيين البحث
                    </a>
                @else
                    <p class="text-muted">لا توجد عاملات متاحة في النظام حالياً</p>
                    <a href="{{ route('RecruitmentContract.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للعقود
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
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
