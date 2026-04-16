<div class="d-flex flex-column gap-3">
    @forelse($activities ?? collect() as $activity)
        @php
            $changedFields = $activity['changed_fields'] ?? [];
            $visibleChanges = array_slice($changedFields, 0, 6);
            $remainingChanges = count($changedFields) - count($visibleChanges);
        @endphp
        <div class="border rounded-3 p-3 bg-white shadow-sm">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary border">{{ $activity['action_label'] ?? 'إجراء' }}</span>
                        <div class="fw-semibold text-dark">{{ $activity['summary'] ?? 'سجل نشاط' }}</div>
                    </div>

                    <div class="small text-muted d-flex flex-wrap align-items-center gap-3">
                        <span><i class="fas fa-user me-1"></i>{{ $activity['actor_name'] ?? 'النظام' }}</span>
                        @if (!empty($activity['actor_role']))
                            <span><i class="fas fa-id-badge me-1"></i>{{ $activity['actor_role'] }}</span>
                        @endif
                        <span><i class="fas fa-calendar-alt me-1"></i>{{ $activity['date_label'] ?? '-' }}</span>
                    </div>
                </div>

                <div class="text-muted small text-end">
                    <div><i class="fas fa-clock me-1"></i>{{ $activity['timestamp_label'] ?? '-' }}</div>
                    @if (!empty($activity['relative_time']))
                        <div>{{ $activity['relative_time'] }}</div>
                    @endif
                </div>
            </div>

            @if (!empty($activity['description']))
                <div class="small text-dark mt-2">{{ $activity['description'] }}</div>
            @endif

            @if (!empty($visibleChanges))
                <div class="small text-muted mt-2">
                    <i class="fas fa-list-ul me-1"></i>
                    تم التعديل على: {{ implode('، ', $visibleChanges) }}
                    @if ($remainingChanges > 0)
                        {{ ' +' . $remainingChanges }}
                    @endif
                </div>
            @endif
        </div>
    @empty
        <div class="border rounded-3 p-3 bg-light text-muted small">
            {{ helperTrans('ingaz.no_records') }}
        </div>
    @endforelse
</div>
