@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
    $singleOffice = $offices->count() === 1 ? $offices->first() : null;
@endphp

@section('page-title', $isAr ? 'رفع عمالة من ملف' : 'Import Workers from File')

@push('style')
<style>
/* ── Drop Zone ── */
.bi-drop-zone {
    position: relative;
    border: 2.5px dashed #cbd5e1;
    border-radius: 20px;
    background: #f8fafc;
    padding: 56px 32px;
    text-align: center;
    cursor: pointer;
    transition: border-color .25s, background .25s, box-shadow .25s;
    user-select: none;
}
.bi-drop-zone:hover,
.bi-drop-zone.drag-over {
    border-color: var(--agent-accent);
    background: #eff6ff;
    box-shadow: 0 0 0 6px rgba(37,99,235,.07);
}
.bi-drop-zone input[type=file] {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}
.bi-drop-icon {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    background: linear-gradient(135deg,#2563eb 0%,#0ea5e9 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    box-shadow: 0 12px 32px rgba(37,99,235,.25);
    transition: transform .3s;
}
.bi-drop-zone:hover .bi-drop-icon,
.bi-drop-zone.drag-over .bi-drop-icon {
    transform: translateY(-4px) scale(1.06);
}
.bi-drop-icon i { font-size: 30px; color: #fff; }
.bi-drop-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}
.bi-drop-sub {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 20px;
}
.bi-type-chips {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 6px;
}
.bi-type-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    border: 1.5px solid;
}
.bi-type-chip.excel { background:#f0fdf4; color:#15803d; border-color:#86efac; }
.bi-type-chip.word  { background:#eff6ff; color:#1d4ed8; border-color:#93c5fd; }
.bi-type-chip.pdf   { background:#fef2f2; color:#b91c1c; border-color:#fca5a5; }

/* ── File Preview Card ── */
.bi-file-preview {
    display: none;
    align-items: center;
    gap: 14px;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 18px;
    margin-top: 20px;
    box-shadow: 0 4px 16px rgba(15,23,42,.06);
}
.bi-file-preview.show { display: flex; }
.bi-file-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.bi-file-icon.excel { background:#f0fdf4; color:#16a34a; }
.bi-file-icon.word  { background:#eff6ff; color:#2563eb; }
.bi-file-icon.pdf   { background:#fef2f2; color:#dc2626; }
.bi-file-icon.other { background:#f1f5f9; color:#475569; }
.bi-file-meta { flex: 1; text-align: start; min-width: 0; }
.bi-file-name {
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #1e293b;
}
.bi-file-size { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.bi-file-remove {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #fee2e2;
    color: #dc2626;
    border: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    font-size: 14px;
}
.bi-file-remove:hover { background: #fecaca; }

/* ── Office single badge ── */
.bi-office-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0f9ff;
    border: 1.5px solid #bae6fd;
    border-radius: 999px;
    padding: 6px 16px;
    font-size: 13px;
    color: #0369a1;
    font-weight: 600;
}

/* ── Submit button ── */
.bi-submit-btn {
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--agent-accent) 0%, #0ea5e9 100%);
    color: #fff;
    border: 0;
    font-size: 15px;
    font-weight: 700;
    padding: 0 32px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(37,99,235,.3);
    transition: opacity .2s, transform .15s;
    width: 100%;
    justify-content: center;
}
.bi-submit-btn:hover { opacity: .92; transform: translateY(-1px); }
.bi-submit-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* ── Recent table ── */
.bi-recent-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}
.bi-recent-badge.pending_review { background:#fef3c7; color:#92400e; }
.bi-recent-badge.completed      { background:#d1fae5; color:#065f46; }
.bi-recent-badge.rejected,
.bi-recent-badge.failed         { background:#fee2e2; color:#991b1b; }
.bi-recent-badge.processing     { background:#e0f2fe; color:#075985; }

/* ── Instructions ── */
.bi-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-top: 16px;
}
.bi-step {
    background: #f8fafc;
    border-radius: 14px;
    padding: 18px;
    text-align: center;
    border: 1px solid #e2e8f0;
}
.bi-step-num {
    width: 36px; height: 36px;
    background: var(--agent-accent);
    color: #fff;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    margin: 0 auto 10px;
}
.bi-step p { font-size: 12px; color: #64748b; margin: 0; line-height: 1.6; }
.bi-step strong { display: block; font-size: 13px; color: #1e293b; margin-bottom: 4px; }

/* ── Drag overlay ── */
.bi-drag-overlay {
    position: fixed;
    inset: 0;
    background: rgba(37,99,235,.08);
    backdrop-filter: blur(2px);
    z-index: 100;
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
    border: 4px dashed var(--agent-accent);
    pointer-events: none;
}
.bi-drag-overlay.show { display: flex; }
.bi-drag-overlay-icon { font-size: 64px; color: var(--agent-accent); animation: bounce .6s infinite alternate; }
.bi-drag-overlay-text { font-size: 22px; font-weight: 700; color: var(--agent-accent); }
@keyframes bounce { to { transform: translateY(-10px); } }
</style>
@endpush

@section('content')


{{-- Full-page drag overlay --}}
<div class="bi-drag-overlay" id="bi-drag-overlay">
    <i class="fa fa-cloud-arrow-up bi-drag-overlay-icon"></i>
    <span class="bi-drag-overlay-text">{{ $isAr ? 'أفلت الملف هنا' : 'Drop file here' }}</span>
</div>

<section class="agent-section">

    {{-- Header --}}
    <div class="agent-section-header">
        <div>
            <h3 style="margin-bottom:2px;">{{ $isAr ? 'رفع عمالة من ملف' : 'Import Workers from File' }}</h3>
            <p style="margin:0;font-size:13px;color:var(--agent-muted);">
                {{ $isAr ? 'Excel · Word · PDF — يتم استخراج البيانات تلقائياً' : 'Excel · Word · PDF — data is auto-extracted' }}
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <a href="{{ route('agent.maids.bulk-import.template', ['type' => 'excel']) }}"
               class="agent-button"
               style="background:#16a34a;gap:8px;">
                <i class="fa fa-file-excel"></i>
                <span>{{ $isAr ? 'نموذج Excel' : 'Excel Template' }}</span>
            </a>
            <a href="{{ route('agent.maids.bulk-import.template', ['type' => 'word']) }}"
               class="agent-button"
               style="background:#2563eb;gap:8px;">
                <i class="fa fa-file-word"></i>
                <span>{{ $isAr ? 'نموذج Word' : 'Word Template' }}</span>
            </a>
            <a href="{{ route('agent.maids.index') }}"
               class="agent-button"
               style="background:var(--agent-muted);gap:8px;">
                <i class="fa fa-chevron-{{ $isAr ? 'right' : 'left' }}"></i>
                <span>{{ $isAr ? 'العودة' : 'Back' }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="agent-alert success" style="margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

        {{-- ── Main Upload Card ── --}}
        <div>
            <div class="agent-card" style="padding:28px;">

                {{-- Office badge (single) or select (multiple) --}}
                @if($singleOffice)
                    <input type="hidden" name="_office_id_display" value="{{ $singleOffice->id }}">
                    <div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                        <span style="font-size:13px;color:#64748b;font-weight:500;">{{ $isAr ? 'المكتب:' : 'Office:' }}</span>
                        <span class="bi-office-badge">
                            <i class="fa fa-building"></i>
                            {{ $singleOffice->title }}
                        </span>
                    </div>
                @endif

                <form action="{{ route('agent.maids.bulk-import.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      id="bi-form">
                    @csrf

                    @if($singleOffice)
                        <input type="hidden" name="office_id" value="{{ $singleOffice->id }}">
                    @else
                        <div style="margin-bottom:20px;">
                            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">
                                {{ $isAr ? 'اختر المكتب' : 'Select Office' }}
                                <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="office_id" required
                                    style="width:100%;height:46px;border:1.5px solid #e2e8f0;border-radius:12px;padding:0 14px;font-size:14px;background:#fff;color:#1e293b;outline:none;">
                                <option value="">{{ $isAr ? '-- اختر المكتب --' : '-- Select Office --' }}</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected' : '' }}>
                                        {{ $office->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('office_id')
                                <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Drop Zone --}}
                    <div class="bi-drop-zone" id="bi-drop-zone">
                        <input type="file"
                               name="file"
                               id="bi-file-input"
                               accept=".xlsx,.xls,.csv,.docx,.doc,.pdf"
                               required>

                        <div class="bi-drop-icon">
                            <i class="fa fa-cloud-arrow-up"></i>
                        </div>
                        <div class="bi-drop-title">
                            {{ $isAr ? 'اسحب الملف وأفلته هنا' : 'Drag & drop your file here' }}
                        </div>
                        <div class="bi-drop-sub">
                            {{ $isAr ? 'أو انقر لاختيار ملف من جهازك' : 'or click to browse from your device' }}
                        </div>
                        <div class="bi-type-chips">
                            <span class="bi-type-chip excel"><i class="fa fa-file-excel"></i> Excel</span>
                            <span class="bi-type-chip word"><i class="fa fa-file-word"></i> Word</span>
                            <span class="bi-type-chip pdf"><i class="fa fa-file-pdf"></i> PDF</span>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;">{{ $isAr ? 'الحجم الأقصى: 10 ميغابايت' : 'Max size: 10 MB' }}</div>
                    </div>

                    @error('file')
                        <p style="color:#ef4444;font-size:12px;margin-top:8px;">{{ $message }}</p>
                    @enderror

                    {{-- File preview --}}
                    <div class="bi-file-preview" id="bi-file-preview">
                        <div class="bi-file-icon" id="bi-file-icon-box">
                            <i id="bi-file-icon-i" class="fa fa-file"></i>
                        </div>
                        <div class="bi-file-meta">
                            <div class="bi-file-name" id="bi-file-name">-</div>
                            <div class="bi-file-size" id="bi-file-size">-</div>
                        </div>
                        <button type="button" class="bi-file-remove" id="bi-file-remove" title="{{ $isAr ? 'حذف' : 'Remove' }}">
                            <i class="fa fa-xmark"></i>
                        </button>
                    </div>

                    {{-- Submit --}}
                    <div style="margin-top:24px;">
                        <button type="submit" class="bi-submit-btn" id="bi-submit" disabled>
                            <i class="fa fa-bolt"></i>
                            {{ $isAr ? 'رفع وتحليل البيانات' : 'Upload & Analyze' }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Recent imports --}}
            @if($recentImports->isNotEmpty())
            <div class="agent-card" style="margin-top:20px;padding:0;overflow:hidden;">
                <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;font-weight:700;font-size:14px;color:#1e293b;">
                    <i class="fa fa-clock-rotate-left" style="color:var(--agent-muted);margin-{{ $isAr ? 'left' : 'right' }}:8px;"></i>
                    {{ $isAr ? 'آخر عمليات الرفع' : 'Recent Uploads' }}
                </div>
                <div class="agent-table-wrap" style="margin:0;">
                    <table class="agent-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>{{ $isAr ? 'الملف' : 'File' }}</th>
                                <th>{{ $isAr ? 'السجلات' : 'Rows' }}</th>
                                <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                                <th>{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentImports as $imp)
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        @php
                                            $iconColor = match($imp->file_type) {
                                                'excel' => '#16a34a', 'word' => '#2563eb', 'pdf' => '#dc2626', default => '#64748b'
                                            };
                                        @endphp
                                        <i class="fa {{ $imp->file_type_icon }}" style="color:{{ $iconColor }};font-size:16px;"></i>
                                        <span style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;" title="{{ $imp->original_filename }}">
                                            {{ $imp->original_filename }}
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $imp->total_rows }}</td>
                                <td>
                                    <span class="bi-recent-badge {{ $imp->status }}">
                                        {{ $imp->status_label }}
                                    </span>
                                </td>
                                <td style="font-size:12px;color:#94a3b8;">{{ $imp->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <a href="{{ route('agent.maids.bulk-import.show', $imp) }}"
                                       style="font-size:12px;color:var(--agent-accent);font-weight:600;text-decoration:none;">
                                        {{ $isAr ? 'عرض' : 'View' }} →
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- ── Sidebar: Steps ── --}}
        <div>
            <div class="agent-card" style="padding:22px;">
                <div style="font-weight:700;font-size:14px;color:#1e293b;margin-bottom:16px;">
                    <i class="fa fa-circle-info" style="color:var(--agent-accent);margin-{{ $isAr ? 'left' : 'right' }}:8px;"></i>
                    {{ $isAr ? 'كيف يعمل؟' : 'How it works?' }}
                </div>
                <div style="display:flex;flex-direction:column;gap:16px;">
                    @php
                        $steps = $isAr ? [
                            ['num'=>'١','icon'=>'fa-file-arrow-up','title'=>'ارفع الملف','desc'=>'Excel أو Word أو PDF يحتوي على بيانات العمالة'],
                            ['num'=>'٢','icon'=>'fa-wand-magic-sparkles','title'=>'استخراج تلقائي','desc'=>'يقوم النظام باستخراج بيانات كل عاملة تلقائياً'],
                            ['num'=>'٣','icon'=>'fa-list-check','title'=>'مراجعة السجلات','desc'=>'راجع البيانات المستخرجة وتأكد من صحتها'],
                            ['num'=>'٤','icon'=>'fa-shield-check','title'=>'موافقة الإدارة','desc'=>'تراجع الإدارة وتعتمد السجلات الصحيحة'],
                        ] : [
                            ['num'=>'1','icon'=>'fa-file-arrow-up','title'=>'Upload File','desc'=>'Excel, Word, or PDF with worker data'],
                            ['num'=>'2','icon'=>'fa-wand-magic-sparkles','title'=>'Auto Extract','desc'=>'System extracts each worker\'s data automatically'],
                            ['num'=>'3','icon'=>'fa-list-check','title'=>'Review Records','desc'=>'Review extracted data and confirm accuracy'],
                            ['num'=>'4','icon'=>'fa-shield-check','title'=>'Admin Approval','desc'=>'Admin reviews and approves valid records'],
                        ];
                    @endphp
                    @foreach($steps as $step)
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--agent-accent),#0ea5e9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;">
                            <i class="fa {{ $step['icon'] }}"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:13px;color:#1e293b;">{{ $step['title'] }}</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;line-height:1.5;">{{ $step['desc'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Download templates --}}
            <div class="agent-card" style="margin-top:16px;padding:20px;">
                <div style="font-weight:700;font-size:14px;color:#1e293b;margin-bottom:6px;">
                    <i class="fa fa-download" style="color:var(--agent-accent);margin-{{ $isAr ? 'left' : 'right' }}:8px;"></i>
                    {{ $isAr ? 'حمّل النموذج الجاهز' : 'Download Template' }}
                </div>
                <p style="font-size:12px;color:#94a3b8;margin-bottom:14px;">
                    {{ $isAr ? 'افتح النموذج، أدخل بيانات العمالة، ثم ارفعه' : 'Open the template, fill in worker data, then upload' }}
                </p>
                <div style="display:flex;flex-direction:column;gap:10px;">

                    {{-- Excel --}}
                    <a href="{{ route('agent.maids.bulk-import.template', ['type' => 'excel']) }}"
                       style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#f0fdf4;border-radius:12px;border:1.5px solid #bbf7d0;text-decoration:none;transition:box-shadow .2s;"
                       onmouseover="this.style.boxShadow='0 4px 14px rgba(22,163,74,.15)'"
                       onmouseout="this.style.boxShadow='none'">
                        <div style="width:40px;height:40px;border-radius:10px;background:#16a34a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa fa-file-excel" style="font-size:18px;color:#fff;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px;font-weight:700;color:#15803d;">{{ $isAr ? 'نموذج Excel' : 'Excel Template' }}</div>
                            <div style="font-size:11px;color:#86efac;">.xlsx · {{ $isAr ? 'كل صف = عاملة واحدة' : 'each row = one worker' }}</div>
                        </div>
                        <i class="fa fa-arrow-down-to-line" style="color:#16a34a;font-size:14px;"></i>
                    </a>

                    {{-- Word --}}
                    <a href="{{ route('agent.maids.bulk-import.template', ['type' => 'word']) }}"
                       style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#eff6ff;border-radius:12px;border:1.5px solid #bfdbfe;text-decoration:none;transition:box-shadow .2s;"
                       onmouseover="this.style.boxShadow='0 4px 14px rgba(37,99,235,.15)'"
                       onmouseout="this.style.boxShadow='none'">
                        <div style="width:40px;height:40px;border-radius:10px;background:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa fa-file-word" style="font-size:18px;color:#fff;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px;font-weight:700;color:#1d4ed8;">{{ $isAr ? 'نموذج Word' : 'Word Template' }}</div>
                            <div style="font-size:11px;color:#93c5fd;">.docx · {{ $isAr ? 'كل كتلة = عاملة' : 'each block = one worker' }}</div>
                        </div>
                        <i class="fa fa-arrow-down-to-line" style="color:#2563eb;font-size:14px;"></i>
                    </a>

                    {{-- PDF note --}}
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#fef2f2;border-radius:12px;border:1.5px solid #fecaca;">
                        <div style="width:40px;height:40px;border-radius:10px;background:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa fa-file-pdf" style="font-size:18px;color:#fff;"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#b91c1c;">PDF</div>
                            <div style="font-size:11px;color:#fca5a5;">{{ $isAr ? 'ارفع ملفات CV الأصلية مباشرة' : 'Upload original CV files directly' }}</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const dropZone   = document.getElementById('bi-drop-zone');
    const fileInput  = document.getElementById('bi-file-input');
    const preview    = document.getElementById('bi-file-preview');
    const iconBox    = document.getElementById('bi-file-icon-box');
    const iconI      = document.getElementById('bi-file-icon-i');
    const nameEl     = document.getElementById('bi-file-name');
    const sizeEl     = document.getElementById('bi-file-size');
    const removeBtn  = document.getElementById('bi-file-remove');
    const submitBtn  = document.getElementById('bi-submit');
    const overlay    = document.getElementById('bi-drag-overlay');

    const TYPE_MAP = {
        xlsx: { cls: 'excel', icon: 'fa-file-excel' },
        xls:  { cls: 'excel', icon: 'fa-file-excel' },
        csv:  { cls: 'excel', icon: 'fa-file-csv'   },
        docx: { cls: 'word',  icon: 'fa-file-word'  },
        doc:  { cls: 'word',  icon: 'fa-file-word'  },
        pdf:  { cls: 'pdf',   icon: 'fa-file-pdf'   },
    };

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function showFile(file) {
        const ext  = file.name.split('.').pop().toLowerCase();
        const info = TYPE_MAP[ext] || { cls: 'other', icon: 'fa-file' };

        iconBox.className = 'bi-file-icon ' + info.cls;
        iconI.className   = 'fa ' + info.icon;
        nameEl.textContent = file.name;
        sizeEl.textContent = formatBytes(file.size);
        preview.classList.add('show');
        dropZone.style.opacity = '.6';
        submitBtn.disabled = false;
    }

    function clearFile() {
        fileInput.value = '';
        preview.classList.remove('show');
        dropZone.style.opacity = '';
        submitBtn.disabled = true;
    }

    fileInput.addEventListener('change', function () {
        if (this.files[0]) showFile(this.files[0]);
    });

    removeBtn.addEventListener('click', clearFile);

    // Drag & drop on zone
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', function () {
        this.classList.remove('drag-over');
    });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showFile(file);
        }
    });

    // Full-page drag overlay
    let dragCounter = 0;
    document.addEventListener('dragenter', function (e) {
        if (e.dataTransfer.types.includes('Files')) {
            dragCounter++;
            overlay.classList.add('show');
        }
    });
    document.addEventListener('dragleave', function () {
        dragCounter--;
        if (dragCounter <= 0) { dragCounter = 0; overlay.classList.remove('show'); }
    });
    document.addEventListener('dragover', function (e) { e.preventDefault(); });
    document.addEventListener('drop', function (e) {
        e.preventDefault();
        dragCounter = 0;
        overlay.classList.remove('show');
        const file = e.dataTransfer.files[0];
        if (file && e.target !== fileInput) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showFile(file);
        }
    });

    // Loading state on submit
    document.getElementById('bi-form').addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ $isAr ? "جارٍ الرفع..." : "Uploading..." }}';
    });
})();
</script>
@endpush
