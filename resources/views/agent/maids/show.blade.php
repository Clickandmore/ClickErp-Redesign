@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
@endphp

@section('page-title', $isAr ? 'تفاصيل السيرة الذاتية' : 'CV Details')

@section('page-action')
    <a href="{{ route('agent.maids.index') }}" class="agent-button" style="background: var(--agent-muted);">
        <i class="fa fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i>
        <span>{{ $isAr ? 'عودة' : 'Back' }}</span>
    </a>
@endsection

@section('content')
    <section class="agent-section" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
            <div style="flex: 0 0 150px; text-align: center;">
                <img src="{{ $maid->photo_url }}" alt="{{ $maid->name }}" style="width: 150px; height: 150px; object-fit: cover; border-radius: 12px; border: 2px solid var(--agent-line);">
                <span class="agent-badge" style="margin-top: 10px; display: inline-block;">
                    {{ $maid->status === 'available' ? ($isAr ? 'متاحة' : 'Available') : ($maid->status === 'contracted' ? ($isAr ? 'متعاقد عليها' : 'Contracted') : ($isAr ? 'محجوزة' : 'Reserved')) }}
                </span>
            </div>
            
            <div style="flex: 1; min-width: 280px;">
                <h3 style="margin: 0 0 10px 0; font-size: 24px; color: var(--agent-primary-dark);">{{ $maid->name ?: '-' }}</h3>
                <div class="agent-detail-grid">
                    <div class="agent-detail-item">
                        <span><i class="fa fa-flag"></i> {{ $isAr ? 'الجنسية' : 'Nationality' }}</span>
                        <strong>{{ $maid->nationality?->sub_title ?: ($maid->nationality?->title ?: '-') }}</strong>
                    </div>
                    <div class="agent-detail-item">
                        <span><i class="fa fa-passport"></i> {{ $isAr ? 'جواز السفر' : 'Passport' }}</span>
                        <strong>{{ $maid->passport_num ?: '-' }}</strong>
                    </div>
                    <div class="agent-detail-item">
                        <span><i class="fa fa-calendar"></i> {{ $isAr ? 'العمر' : 'Age' }}</span>
                        <strong>{{ $maid->age ?: '-' }} {{ $isAr ? 'سنة' : 'Years' }}</strong>
                    </div>
                    <div class="agent-detail-item">
                        <span><i class="fa fa-pray"></i> {{ $isAr ? 'الديانة' : 'Religion' }}</span>
                        <strong>{{ $maid->religion?->title ?: '-' }}</strong>
                    </div>
                    <div class="agent-detail-item">
                        <span><i class="fa fa-briefcase"></i> {{ $isAr ? 'المهن' : 'Jobs' }}</span>
                        <strong>{{ $maid->jobTypes->pluck('title')->implode(', ') ?: '-' }}</strong>
                    </div>
                    <div class="agent-detail-item">
                        <span><i class="fa fa-money-bill"></i> {{ $isAr ? 'السعر' : 'Price' }}</span>
                        <strong>{{ $maid->resolved_price ? formatMoney($maid->resolved_price) : '-' }}</strong>
                    </div>
                    <div class="agent-detail-item">
                        <span><i class="fa fa-wallet"></i> {{ $isAr ? 'الراتب' : 'Salary' }}</span>
                        <strong>{{ $maid->resolved_salary ? formatMoney($maid->resolved_salary) : '-' }}</strong>
                    </div>
                    <div class="agent-detail-item">
                        <span><i class="fa fa-star"></i> {{ $isAr ? 'الخبرة' : 'Experience' }}</span>
                        <strong>
                            @if($maid->experience_status === 'has_experience')
                                {{ $isAr ? 'سبق لها العمل' : 'Has Experience' }}
                            @elseif($maid->experience_status === 'new')
                                {{ $isAr ? 'جديدة' : 'New' }}
                            @else
                                -
                            @endif
                            {{ $maid->experience_years ? '(' . $maid->experience_years . ' ' . ($isAr ? 'سنوات' : 'years') . ')' : '' }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <section class="agent-section">
            <h4 style="margin: 0 0 15px 0; border-bottom: 1px solid var(--agent-line); padding-bottom: 10px;">
                <i class="fa fa-info-circle"></i> {{ $isAr ? 'معلومات إضافية' : 'Additional Info' }}
            </h4>
            <div class="agent-detail-grid" style="grid-template-columns: 1fr;">
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'المهارات' : 'Skills' }}</span>
                    <strong>{{ is_array($maid->skills) ? implode(', ', $maid->skills) : ($maid->skills ?: '-') }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'اللغات' : 'Languages' }}</span>
                    <strong>{{ is_array($maid->languages) ? implode(', ', $maid->languages) : ($maid->languages ?: '-') }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'عدد الأطفال' : 'Children Count' }}</span>
                    <strong>{{ $maid->childrens_count ?? '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'الوزن' : 'Weight' }}</span>
                    <strong>{{ $maid->weight ? $maid->weight . ' kg' : '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'الطول' : 'Height' }}</span>
                    <strong>{{ $maid->height ? $maid->height . ' cm' : '-' }}</strong>
                </div>
            </div>
        </section>

        <section class="agent-section">
            <h4 style="margin: 0 0 15px 0; border-bottom: 1px solid var(--agent-line); padding-bottom: 10px;">
                <i class="fa fa-file-pdf"></i> {{ $isAr ? 'المرفقات' : 'Attachments' }}
            </h4>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @if($maid->cv_file)
                    <a href="{{ $maid->cv_file_url }}" target="_blank" class="agent-button" style="justify-content: center; background: #f8fafc; color: var(--agent-ink); border: 1px solid var(--agent-line);">
                        <i class="fa fa-download" style="color: var(--agent-accent);"></i>
                        {{ $isAr ? 'تحميل السيرة الذاتية (الأصل)' : 'Download Original CV' }}
                    </a>
                @endif
                
                @if($maid->cv_preview)
                    <a href="{{ $maid->cv_preview_url }}" target="_blank" class="agent-button" style="justify-content: center; background: #f8fafc; color: var(--agent-ink); border: 1px solid var(--agent-line);">
                        <i class="fa fa-eye" style="color: var(--agent-primary);"></i>
                        {{ $isAr ? 'عرض السيرة الذاتية (النظام)' : 'View System CV' }}
                    </a>
                @endif

                @if($maid->video_link)
                    <a href="{{ $maid->video_link }}" target="_blank" class="agent-button" style="justify-content: center; background: #f8fafc; color: var(--agent-ink); border: 1px solid var(--agent-line);">
                        <i class="fa fa-video" style="color: #dc2626;"></i>
                        {{ $isAr ? 'رابط الفيديو' : 'Video Link' }}
                    </a>
                @endif

                @if(!$maid->cv_file && !$maid->cv_preview && !$maid->video_link)
                    <p class="agent-empty">{{ $isAr ? 'لا توجد مرفقات.' : 'No attachments available.' }}</p>
                @endif
            </div>

            @if($maid->additional_notes)
            <div style="margin-top: 20px;">
                <h4 style="margin: 0 0 15px 0; border-bottom: 1px solid var(--agent-line); padding-bottom: 10px;">
                    <i class="fa fa-sticky-note"></i> {{ $isAr ? 'ملاحظات' : 'Notes' }}
                </h4>
                <div style="padding: 15px; background: #fef9c3; border-radius: 8px; border: 1px solid #fde047; color: #854d0e;">
                    {{ $maid->additional_notes }}
                </div>
            </div>
            @endif
        </section>
    </div>
@endsection
