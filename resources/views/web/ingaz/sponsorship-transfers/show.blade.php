@extends('web.layout.app')

@section('content')
<main>
    <!-- page header -->
    <section class="page_header">
        <h1>تفاصيل طلب نقل الكفالة</h1>
        <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / <a href="{{route('web.profile')}}">لوحة التحكم</a> / <a href="{{route('web.sponsorship-transfers.index')}}">طلبات نقل الكفالة</a> / تفاصيل الطلب</h6>
    </section>

    <!-- transfer details section -->
    <section class="transfer_details_section">
        <div class="container">
            <div class="row">
                @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="transfer_details_container">
                        <!-- Header -->
                        <div class="section_header mb-4">
                            <div class="header_content">
                                <h3>طلب نقل كفالة #{{ $transfer->id }}</h3>
                                <div class="status_badge status_{{ $transfer->status }}">
                                    {{ $transfer->status_text }}
                                </div>
                            </div>
                            <div class="header_meta">
                                <span class="created_date">تاريخ الإنشاء: {{ $transfer->created_at->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>

                        <!-- Transfer Information -->
                        <div class="transfer_info_card">
                            <h5>معلومات النقل</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info_item">
                                        <span class="info_label">رقم العقد:</span>
                                        <span class="info_value">#{{ $transfer->contract_id }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="info_label">قيمة العقد:</span>
                                        <span class="info_value">{{ number_format($transfer->contract_value, 2) }} ريال</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="info_label">المبلغ المدفوع:</span>
                                        <span class="info_value">{{ number_format($transfer->paid_amount, 2) }} ريال</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="info_label">المبلغ المتبقي:</span>
                                        <span class="info_value">{{ number_format($transfer->remaining_amount, 2) }} ريال</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info_item">
                                        <span class="info_label">فترة التجربة:</span>
                                        <span class="info_value">{{ $transfer->trial_days }} أيام</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="info_label">تاريخ بداية التجربة:</span>
                                        <span class="info_value">{{ $transfer->trial_start_date ? $transfer->trial_start_date->format('Y-m-d') : 'غير محدد' }}</span>
                                    </div>
                                    @if($transfer->notes)
                                    <div class="info_item">
                                        <span class="info_label">ملاحظات:</span>
                                        <span class="info_value">{{ $transfer->notes }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Sponsors Information -->
                        <div class="sponsors_info_card">
                            <h5>معلومات الكفلاء</h5>
                            <div class="transfer_direction">
                                @if($transfer->old_sponsor_id == auth()->id())
                                    <div class="direction_info">
                                        <span class="direction_label">أنت كفيل سابق</span>
                                        <div class="direction_arrow">
                                            <div class="sponsor_card old_sponsor">
                                                <h6>{{ auth()->user()->name }}</h6>
                                                <p>{{ auth()->user()->phone }}</p>
                                                <p>{{ auth()->user()->address }}</p>
                                            </div>
                                            <div class="arrow_icon">
                                                <i class="fa-regular fa-arrow-right"></i>
                                            </div>
                                            <div class="sponsor_card new_sponsor">
                                                <h6>{{ $transfer->newSponsor ? $transfer->newSponsor->name : 'غير محدد' }}</h6>
                                                <p>{{ $transfer->newSponsor ? $transfer->newSponsor->phone : 'غير محدد' }}</p>
                                                <p>{{ $transfer->newSponsor ? $transfer->newSponsor->address : 'غير محدد' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="direction_info">
                                        <span class="direction_label">أنت كفيل جديد</span>
                                        <div class="direction_arrow">
                                            <div class="sponsor_card old_sponsor">
                                                <h6>{{ $transfer->oldSponsor ? $transfer->oldSponsor->name : 'غير محدد' }}</h6>
                                                <p>{{ $transfer->oldSponsor ? $transfer->oldSponsor->phone : 'غير محدد' }}</p>
                                                <p>{{ $transfer->oldSponsor ? $transfer->oldSponsor->address : 'غير محدد' }}</p>
                                            </div>
                                            <div class="arrow_icon">
                                                <i class="fa-regular fa-arrow-right"></i>
                                            </div>
                                            <div class="sponsor_card new_sponsor">
                                                <h6>{{ auth()->user()->name }}</h6>
                                                <p>{{ auth()->user()->phone }}</p>
                                                <p>{{ auth()->user()->address }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Contract Information -->
                        @if($transfer->contract)
                        <div class="contract_info_card">
                            <h5>معلومات العقد</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info_item">
                                        <span class="info_label">العاملة:</span>
                                        <span class="info_value">{{ $transfer->contract->maid ? $transfer->contract->maid->name : 'غير محدد' }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="info_label">الجنسية:</span>
                                        <span class="info_value">{{ $transfer->contract->maid && $transfer->contract->maid->nationality ? $transfer->contract->maid->nationality->sub_title : 'غير محدد' }}</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="info_label">تاريخ العقد:</span>
                                        <span class="info_value">{{ $transfer->contract->contract_date ? \Carbon\Carbon::parse($transfer->contract->contract_date)->format('Y-m-d') : 'غير محدد' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info_item">
                                        <span class="info_label">الراتب:</span>
                                        <span class="info_value">{{ $transfer->contract->info ? $transfer->contract->info->salary : 'غير محدد' }} ريال</span>
                                    </div>
                                    <div class="info_item">
                                        <span class="info_label">حالة العقد:</span>
                                        <span class="info_value">{{ $transfer->contract->status }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Transfer Timeline -->
                        <div class="timeline_card">
                            <h5>خط سير الطلب</h5>
                            <div class="timeline">
                                <div class="timeline_item {{ $transfer->status != 'pending' ? 'completed' : '' }}">
                                    <div class="timeline_icon">
                                        <i class="fa-regular fa-clock"></i>
                                    </div>
                                    <div class="timeline_content">
                                        <h6>تم إنشاء الطلب</h6>
                                        <p>{{ $transfer->created_at->format('Y-m-d H:i') }}</p>
                                    </div>
                                </div>

                                <div class="timeline_item {{ in_array($transfer->status, ['approved', 'trial', 'transferred', 'failed']) ? 'completed' : '' }}">
                                    <div class="timeline_icon">
                                        <i class="fa-regular fa-check-circle"></i>
                                    </div>
                                    <div class="timeline_content">
                                        <h6>تمت الموافقة</h6>
                                        <p>{{ $transfer->approved_at ? $transfer->approved_at->format('Y-m-d H:i') : 'في الانتظار' }}</p>
                                        @if($transfer->approvedBy)
                                            <small>بواسطة: {{ $transfer->approvedBy->name }}</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="timeline_item {{ in_array($transfer->status, ['trial', 'transferred', 'failed']) ? 'completed' : '' }}">
                                    <div class="timeline_icon">
                                        <i class="fa-regular fa-play-circle"></i>
                                    </div>
                                    <div class="timeline_content">
                                        <h6>فترة التجربة</h6>
                                        <p>{{ $transfer->trial_started_at ? $transfer->trial_started_at->format('Y-m-d H:i') : 'في الانتظار' }}</p>
                                    </div>
                                </div>

                                @if($transfer->status == 'transferred')
                                <div class="timeline_item completed">
                                    <div class="timeline_icon">
                                        <i class="fa-regular fa-check-double"></i>
                                    </div>
                                    <div class="timeline_content">
                                        <h6>تم النقل بنجاح</h6>
                                        <p>{{ $transfer->transferred_at ? $transfer->transferred_at->format('Y-m-d H:i') : '' }}</p>
                                        @if($transfer->transferredBy)
                                            <small>بواسطة: {{ $transfer->transferredBy->name }}</small>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if($transfer->status == 'failed')
                                <div class="timeline_item failed">
                                    <div class="timeline_icon">
                                        <i class="fa-regular fa-times-circle"></i>
                                    </div>
                                    <div class="timeline_content">
                                        <h6>فشل التجربة</h6>
                                        <p>{{ $transfer->failed_at ? $transfer->failed_at->format('Y-m-d H:i') : '' }}</p>
                                        @if($transfer->failure_reason)
                                            <small class="text-danger">السبب: {{ $transfer->failure_reason }}</small>
                                        @endif
                                        @if($transfer->failedBy)
                                            <small>بواسطة: {{ $transfer->failedBy->name }}</small>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if($transfer->status == 'rejected')
                                <div class="timeline_item failed">
                                    <div class="timeline_icon">
                                        <i class="fa-regular fa-times-circle"></i>
                                    </div>
                                    <div class="timeline_content">
                                        <h6>تم رفض الطلب</h6>
                                        <p>{{ $transfer->updated_at->format('Y-m-d H:i') }}</p>
                                        @if($transfer->rejection_reason)
                                            <small class="text-danger">السبب: {{ $transfer->rejection_reason }}</small>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="actions_card">
                            <a href="{{ route('web.sponsorship-transfers.index') }}" class="btn btn-secondary">
                                <i class="fa-regular fa-arrow-left me-2"></i>
                                العودة للقائمة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.transfer_details_section {
    padding: 40px 0;
}

.transfer_details_container {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section_header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 20px;
}

.header_content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.header_content h3 {
    margin: 0;
    color: #333;
    font-weight: 600;
}

.status_badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.status_pending { background: #fff3cd; color: #856404; }
.status_approved { background: #d1ecf1; color: #0c5460; }
.status_trial { background: #d4edda; color: #155724; }
.status_transferred { background: #d1e7dd; color: #0f5132; }
.status_failed { background: #f8d7da; color: #721c24; }
.status_rejected { background: #f8d7da; color: #721c24; }

.header_meta {
    color: #666;
    font-size: 0.9rem;
}

.transfer_info_card, .sponsors_info_card, .contract_info_card, .timeline_card, .actions_card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.transfer_info_card h5, .sponsors_info_card h5, .contract_info_card h5, .timeline_card h5 {
    color: #333;
    margin-bottom: 15px;
    font-weight: 600;
}

.info_item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.info_label {
    font-weight: 500;
    color: #666;
}

.info_value {
    color: #333;
    font-weight: 600;
}

.transfer_direction {
    background: #e3f2fd;
    border-radius: 8px;
    padding: 20px;
}

.direction_label {
    display: block;
    font-weight: 600;
    color: #1976d2;
    margin-bottom: 15px;
    text-align: center;
}

.direction_arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
}

.sponsor_card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    min-width: 200px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.sponsor_card h6 {
    margin: 0 0 10px 0;
    color: #333;
    font-weight: 600;
}

.sponsor_card p {
    margin: 5px 0;
    color: #666;
    font-size: 0.9rem;
}

.arrow_icon {
    color: #1976d2;
    font-size: 1.5rem;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline_item {
    position: relative;
    margin-bottom: 25px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.timeline_item::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 20px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #6c757d;
    border: 3px solid #fff;
}

.timeline_item.completed::before {
    background: #28a745;
}

.timeline_item.failed::before {
    background: #dc3545;
}

.timeline_icon {
    display: inline-block;
    margin-right: 10px;
    color: #6c757d;
}

.timeline_item.completed .timeline_icon {
    color: #28a745;
}

.timeline_item.failed .timeline_icon {
    color: #dc3545;
}

.timeline_content h6 {
    margin: 0;
    font-weight: 600;
    color: #333;
}

.timeline_content p {
    margin: 5px 0;
    color: #666;
    font-size: 0.9rem;
}

.timeline_content small {
    color: #999;
    font-size: 0.8rem;
}

.actions_card {
    text-align: center;
    padding: 20px;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
    border: none;
}

.btn-secondary:hover {
    background: #5a6268;
    color: #fff;
}
</style>
@endsection
