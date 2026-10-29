{{-- جدول سجل MCP: يُستخدم في «نظرة عامة» و«السجل» بشاشة Super Admin ← MCP --}}
@php $labels = \App\Models\McpAuditLog::STATUS_LABELS; @endphp
@if (collect($rows)->isEmpty())
    <div class="mcp-empty">لا توجد سجلات مطابقة.</div>
@else
    <div class="table-wrap" style="margin-top:0">
        <table>
            <thead><tr><th>الوقت</th><th>الموظف</th><th>الأداة</th><th>المدخلات</th><th>النتيجة</th><th>المدة</th></tr></thead>
            <tbody>
            @foreach ($rows as $log)
                @php
                    $tone = match ($log->status) { 'ok' => 'ok', 'error' => 'bad', 'unknown' => 'bad', default => 'warn' };
                    $args = json_encode($log->arguments ?: new \stdClass(), JSON_UNESCAPED_UNICODE);
                @endphp
                <tr>
                    <td dir="ltr">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $names[$log->admin_id] ?? ($log->admin_id ? '#' . $log->admin_id : '—') }}</td>
                    <td>
                        @if ($log->method === 'tools/call')
                            <code dir="ltr">{{ $log->tool }}</code>
                        @else
                            <span class="text-muted" dir="ltr">{{ $log->method }}</span>@if ($log->tool) <small class="text-muted">— {{ $log->tool }}</small>@endif
                        @endif
                    </td>
                    <td><span class="mcp-args" title="{{ $args }}">{{ $args }}</span></td>
                    <td><span class="badge {{ $tone }}" @if ($log->error) title="{{ $log->error }}" @endif>{{ $labels[$log->status] ?? $log->status }}</span></td>
                    <td dir="ltr">{{ $log->duration_ms }} ms</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
