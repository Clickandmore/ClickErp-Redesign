@include('admin.superadmin.system-updates._styles')

@php
    $update = $systemUpdate;
    $acknowledged = $reportRows->filter(fn ($row) => $row['acknowledged_at'])->count();
    $seenOnly = $reportRows->filter(fn ($row) => $row['seen_at'] && ! $row['acknowledged_at'])->count();
    $notSeen = $reportRows->count() - $acknowledged - $seenOnly;
@endphp

<section class="panel">
    <div class="panel-head su-toolbar">
        <div>
            <h2 class="panel-title">من اطلع على: {{ $update->title }}</h2>
            <p class="panel-subtitle">
                {{ $update->categoryMeta()['label'] }}
                · نُشر {{ $update->published_at ? $update->published_at->translatedFormat('j M Y - h:i a') : '—' }}
                @if ($update->isRemote()) · من المصدر <span class="su-code">{{ $update->source_host }}</span> @endif
            </p>
        </div>
        <div class="su-actions">
            @unless ($update->isRemote())
                <a class="btn" href="{{ route('dev-superadmin.system-updates.edit', $update) }}">تعديل</a>
            @endunless
            <a class="btn" href="{{ route('dev-superadmin.product-updates') }}">رجوع للقائمة</a>
        </div>
    </div>
    <div class="panel-body">
        <div class="su-state" style="margin-top:0">
            <div><span>أكدوا الاطلاع</span><strong style="color:var(--success)">{{ $acknowledged }}</strong></div>
            <div><span>شاهدوه دون تأكيد</span><strong style="color:var(--warning)">{{ $seenOnly }}</strong></div>
            <div><span>لم يشاهدوه</span><strong style="color:var(--danger)">{{ $notSeen }}</strong></div>
        </div>

        @if ($update->summary || $update->body)
            <details style="margin-top:16px">
                <summary style="cursor:pointer; font-weight:600; color:var(--text-soft)">محتوى التحديث</summary>
                <div class="su-preview-body" style="margin-top:10px">
                    @if ($update->summary)<p><b>{{ $update->summary }}</b></p>@endif
                    {!! $update->renderedBody() !!}
                </div>
            </details>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>الموظف</th><th>الحالة</th><th>شاهده</th><th>أكّد</th></tr>
                </thead>
                <tbody>
                    @forelse ($reportRows as $row)
                        <tr>
                            <td>
                                <div class="su-title">{{ $row['admin']->name }}</div>
                                <div class="su-sub su-code">{{ $row['admin']->email ?: $row['admin']->username }}</div>
                            </td>
                            <td>
                                @if ($row['acknowledged_at'])
                                    <span class="su-pill is-good">أكّد</span>
                                @elseif ($row['seen_at'])
                                    <span class="su-pill is-warn">شاهد فقط</span>
                                @else
                                    <span class="su-pill">لم يشاهد</span>
                                @endif
                            </td>
                            <td style="font-size:.82rem">{{ $row['seen_at'] ? $row['seen_at']->translatedFormat('j M Y - h:i a') : '—' }}</td>
                            <td style="font-size:.82rem">{{ $row['acknowledged_at'] ? $row['acknowledged_at']->translatedFormat('j M Y - h:i a') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="su-sub">لا يوجد موظفون نشطون.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
