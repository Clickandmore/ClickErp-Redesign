@if($dispatch && !empty($dispatch->attachments))
    @include('admin.ingaz.RecruitmentContract.partials.dispatch-attachments-styles')
    @php
        $attachmentKinds = [
            'is-image' => ['fa-file-image', ['jpg', 'jpeg', 'png']],
            'is-pdf' => ['fa-file-pdf', ['pdf']],
            'is-word' => ['fa-file-word', ['doc', 'docx']],
            'is-excel' => ['fa-file-excel', ['xls', 'xlsx']],
        ];
    @endphp
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-2">
            <h5 class="mb-0"><i class="fas fa-paperclip text-primary me-1"></i> مرفقات الإرسالية / فاتورة المورد</h5>
            <span class="badge rounded-pill bg-light text-secondary border">{{ count($dispatch->attachments) }} ملف</span>
        </div>
        <div class="card-body">
            <div class="dispatch-attachments__list">
                @foreach($dispatch->attachments as $attachment)
                    @php
                        $attachmentName = $attachment['name'] ?? basename($attachment['path']);
                        $extension = strtolower(pathinfo($attachmentName, PATHINFO_EXTENSION) ?: pathinfo($attachment['path'], PATHINFO_EXTENSION));
                        [$kindClass, $kindIcon] = ['is-other', 'fa-file'];
                        foreach ($attachmentKinds as $class => [$icon, $extensions]) {
                            if (in_array($extension, $extensions, true)) {
                                [$kindClass, $kindIcon] = [$class, $icon];
                            }
                        }
                    @endphp
                    <a href="{{ showFile($attachment['path']) }}" target="_blank" rel="noopener" class="dispatch-attachments__saved" title="فتح {{ $attachmentName }}">
                        <span class="dispatch-attachments__file-icon {{ $kindClass }}"><i class="far {{ $kindIcon }}"></i></span>
                        <span class="dispatch-attachments__file-body">
                            <span class="dispatch-attachments__file-name">{{ $attachmentName }}</span>
                            <span class="dispatch-attachments__file-size">{{ strtoupper($extension) ?: 'ملف' }}</span>
                        </span>
                        <i class="fas fa-external-link-alt text-muted small"></i>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
