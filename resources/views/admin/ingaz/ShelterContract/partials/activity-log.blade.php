@if($logs && $logs->count() > 0)
    <div class="activity-log">
        @foreach($logs as $log)
            <div class="activity-item mb-3 p-3 border rounded bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <div class="activity-icon me-3">
                                @switch($log->action_type)
                                    @case("update")
                                        <i class="fas fa-edit text-primary"></i>
                                        @break
                                    @case("confirm")
                                        <i class="fas fa-check-double text-success"></i>
                                        @break
                                    @case("complete")
                                        <i class="fas fa-flag-checkered text-warning"></i>
                                        @break
                                    @case("attachment_added")
                                        <i class="fas fa-paperclip text-info"></i>
                                        @break
                                    @default
                                        <i class="fas fa-circle text-secondary"></i>
                                @endswitch
                            </div>
                            <div class="activity-content">
                                <div class="activity-description fw-bold">
                                    {{ $log->description }}
                                </div>
                                @if($log->field_name && $log->old_value !== $log->new_value)
                                    <div class="activity-details small text-muted mt-1">
                                        <span class="badge bg-light text-dark">{{ $log->field_name_text }}</span>
                                        @if($log->old_value !== null)
                                            <span class="text-danger">من: {{ $log->old_value }}</span>
                                        @endif
                                        @if($log->new_value !== null)
                                            <span class="text-success">إلى: {{ $log->new_value }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if($log->attachments && count($log->attachments) > 0)
                                    <div class="activity-attachments mt-2">
                                        <small class="text-muted">المرفقات:</small>
                                        @foreach($log->attachments as $attachment)
                                            <span class="badge bg-info me-1">{{ $attachment }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="activity-meta text-end">
                        <div class="activity-user small text-muted">
                            <i class="fas fa-user"></i> {{ $log->admin->name ?? "غير محدد" }}
                        </div>
                        <div class="activity-time small text-muted">
                            <i class="fas fa-clock"></i> {{ $log->created_at->format("Y-m-d H:i") }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center text-muted py-4">
        <i class="fas fa-info-circle fa-2x mb-3"></i>
        <p>لا توجد تحركات مسجلة بعد</p>
    </div>
@endif
