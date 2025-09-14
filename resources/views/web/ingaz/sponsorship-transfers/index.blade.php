@extends('web.layout.app')

@section('content')
<main>
    <!-- page header -->
    <section class="page_header">
        <h1>طلبات نقل الكفالة</h1>
        <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / <a href="{{route('web.profile')}}">لوحة التحكم</a> / طلبات نقل الكفالة</h6>
    </section>

    <!-- sponsorship transfers section -->
    <section class="setting_setion">
        <div class="container">
            <div class="row">
                @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="transfers_container">
                        <!-- Header -->
                        <div class="section_header mb-4">
                            <div class="header_content">
                                <h3>طلبات نقل الكفالة</h3>
                                <p>تابع حالة طلبات نقل الكفالة الخاصة بك</p>
                            </div>
                        </div>

                        @if($transfers->count() > 0)
                            <!-- Transfers List -->
                            <div class="transfers_list">
                                @foreach($transfers as $transfer)
                                <div class="transfer_card">
                                    <div class="transfer_header">
                                        <div class="transfer_info">
                                            <h5>طلب نقل كفالة #{{ $transfer->id }}</h5>
                                            <span class="transfer_date">{{ $transfer->created_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                        <div class="transfer_status">
                                            <span class="status_badge status_{{ $transfer->status }}">
                                                {{ $transfer->status_text }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="transfer_details">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="detail_item">
                                                    <span class="detail_label">رقم العقد:</span>
                                                    <span class="detail_value">#{{ $transfer->contract_id }}</span>
                                                </div>
                                                <div class="detail_item">
                                                    <span class="detail_label">قيمة العقد:</span>
                                                    <span class="detail_value">{{ number_format($transfer->contract_value, 2) }} ريال</span>
                                                </div>
                                                <div class="detail_item">
                                                    <span class="detail_label">المبلغ المدفوع:</span>
                                                    <span class="detail_value">{{ number_format($transfer->paid_amount, 2) }} ريال</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="detail_item">
                                                    <span class="detail_label">فترة التجربة:</span>
                                                    <span class="detail_value">{{ $transfer->trial_days }} أيام</span>
                                                </div>
                                                <div class="detail_item">
                                                    <span class="detail_label">تاريخ بداية التجربة:</span>
                                                    <span class="detail_value">{{ $transfer->trial_start_date ? $transfer->trial_start_date->format('Y-m-d') : 'غير محدد' }}</span>
                                                </div>
                                                <div class="detail_item">
                                                    <span class="detail_label">المبلغ المتبقي:</span>
                                                    <span class="detail_value">{{ number_format($transfer->remaining_amount, 2) }} ريال</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Transfer Direction -->
                                        <div class="transfer_direction mt-3">
                                            @if($transfer->old_sponsor_id == auth()->id())
                                                <div class="direction_info">
                                                    <span class="direction_label">أنت كفيل سابق</span>
                                                    <div class="direction_arrow">
                                                        <span class="old_sponsor">{{ auth()->user()->name }}</span>
                                                        <i class="fa-regular fa-arrow-right"></i>
                                                        <span class="new_sponsor">{{ $transfer->newSponsor ? $transfer->newSponsor->name : 'غير محدد' }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="direction_info">
                                                    <span class="direction_label">أنت كفيل جديد</span>
                                                    <div class="direction_arrow">
                                                        <span class="old_sponsor">{{ $transfer->oldSponsor ? $transfer->oldSponsor->name : 'غير محدد' }}</span>
                                                        <i class="fa-regular fa-arrow-right"></i>
                                                        <span class="new_sponsor">{{ auth()->user()->name }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Transfer Timeline -->
                                        <div class="transfer_timeline mt-4">
                                            <h6>خط سير الطلب:</h6>
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
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if($transfer->notes)
                                        <div class="transfer_notes mt-3">
                                            <h6>ملاحظات:</h6>
                                            <p class="notes_text">{{ $transfer->notes }}</p>
                                        </div>
                                        @endif

                                        <div class="transfer_actions mt-3">
                                            <a href="{{ route('web.sponsorship-transfers.show', $transfer->id) }}" class="btn btn-primary btn-sm">
                                                <i class="fa-regular fa-eye me-1"></i>
                                                عرض التفاصيل
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="pagination_container mt-4">
                                {{ $transfers->links() }}
                            </div>
                        @else
                            <!-- No Transfers -->
                            <div class="no_transfers">
                                <div class="no_transfers_icon">
                                    <i class="fa-regular fa-exchange-alt"></i>
                                </div>
                                <h5>لا توجد طلبات نقل كفالة</h5>
                                <p>لم يتم إنشاء أي طلبات نقل كفالة بعد</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.transfers_container {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.transfer_card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}

.transfer_header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.transfer_info h5 {
    margin: 0;
    color: #333;
    font-weight: 600;
}

.transfer_date {
    color: #666;
    font-size: 0.9rem;
}

.status_badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status_pending { background: #fff3cd; color: #856404; }
.status_approved { background: #d1ecf1; color: #0c5460; }
.status_trial { background: #d4edda; color: #155724; }
.status_transferred { background: #d1e7dd; color: #0f5132; }
.status_failed { background: #f8d7da; color: #721c24; }
.status_rejected { background: #f8d7da; color: #721c24; }

.transfer_details {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
}

.detail_item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.detail_label {
    font-weight: 500;
    color: #666;
}

.detail_value {
    color: #333;
    font-weight: 600;
}

.transfer_direction {
    background: #e3f2fd;
    border-radius: 8px;
    padding: 15px;
}

.direction_label {
    display: block;
    font-weight: 600;
    color: #1976d2;
    margin-bottom: 10px;
}

.direction_arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.direction_arrow i {
    color: #1976d2;
    font-size: 1.2rem;
}

.old_sponsor, .new_sponsor {
    background: #fff;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
    color: #333;
}

.transfer_timeline {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
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
    margin-bottom: 20px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.timeline_item::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 15px;
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
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9rem;
}

.transfer_notes {
    background: #fff3cd;
    border-radius: 8px;
    padding: 15px;
}

.notes_text {
    margin: 0;
    color: #856404;
}

.transfer_actions {
    text-align: center;
}

.no_transfers {
    text-align: center;
    padding: 60px 20px;
}

.no_transfers_icon {
    font-size: 4rem;
    color: #ccc;
    margin-bottom: 20px;
}

.no_transfers h5 {
    color: #666;
    margin-bottom: 10px;
}

.no_transfers p {
    color: #999;
}

.pagination_container {
    text-align: center;
}
</style>
@endsection
