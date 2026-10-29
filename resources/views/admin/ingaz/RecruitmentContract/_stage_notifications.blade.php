@php
    $stageNotifications = \App\Models\RecruitmentStageNotification::where('contract_id', $contract->id)->latest('id')->limit(50)->get();
    $deliveryLabels = ['pending' => 'بانتظار الإرسال', 'processing' => 'جارٍ الإرسال', 'sent' => 'أُرسل', 'failed' => 'فشل', 'skipped' => 'لم يُرسل', 'cancelled' => 'أُلغي'];
@endphp
@if ($stageNotifications->isNotEmpty())
    <div class="info-card mb-4">
        <div class="card-title">إشعارات متابعة العقد — آخر 50 إشعارًا</div>
        <div class="table-responsive"><table class="table">
            <thead><tr><th>القناة</th><th>الرسالة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>@foreach ($stageNotifications as $notification)
                <tr>
                    <td>{{ $notification->channel === 'email' ? 'بريد إلكتروني' : 'SMS' }}</td>
                    <td>{{ $notification->message }}</td>
                    <td>{{ $deliveryLabels[$notification->status] ?? $notification->status }}
                        @if ($notification->error)<div class="small text-muted">{{ $notification->error }}</div>@endif</td>
                    <td>{{ $notification->sent_at ?? $notification->created_at }}</td>
                    <td>@if ($canUpdateContractStageSteps && in_array($notification->status, ['failed', 'skipped'], true))
                        <form method="POST" action="{{ route('RecruitmentContract.stageNotifications.retry', [$contract->id, $notification->id]) }}">
                            @csrf <button type="submit" class="btn btn-sm btn-outline-primary">إعادة المحاولة</button>
                        </form>
                    @endif</td>
                </tr>
            @endforeach</tbody>
        </table></div>
    </div>
@endif
