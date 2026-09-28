{{-- بطاقات الهوية: رفع الوجه/الخلف وتاريخ الانتهاء، وتوثيقها يجعل العميل «موثّقًا». --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $statusStyle = ['pending' => 'background:#fef3c7;color:#92400e', 'verified' => 'background:#dcfce7;color:#166534', 'rejected' => 'background:#fee2e2;color:#991b1b'];
    $isImage = fn ($path) => $path && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);
@endphp
<div class="hc-note mb-3"><i class="fa fa-circle-info mt-1"></i><div>{{ $c('documents.hint') }}</div></div>

@if($canEdit)
    <form method="POST" action="{{ route('admin.hourly-customers.documents.store', $customer->id) }}" enctype="multipart/form-data" class="hc-form mb-4">
        @csrf
        <fieldset>
            <legend><i class="fa fa-upload"></i> {{ $c('documents.add') }}</legend>
            <div class="hc-row">
                <div class="hc-field">
                    <label for="hc-doc-type">{{ $c('documents.type') }} *</label>
                    <select class="form-select" id="hc-doc-type" name="doc_type" required>
                        @foreach(\App\Models\Hourly\HourlyCustomerDocument::TYPES as $type)<option value="{{ $type }}" {{ old('doc_type') === $type ? 'selected' : '' }}>{{ $c('identity_types.'.$type) }}</option>@endforeach
                    </select>
                </div>
                <div class="hc-field">
                    <label for="hc-doc-number">{{ $c('documents.number') }}</label>
                    <input class="form-control" id="hc-doc-number" name="number" maxlength="50" dir="ltr" value="{{ old('number', $customer->national_num) }}">
                </div>
                <div class="hc-field">
                    <label for="hc-doc-expires">{{ $c('documents.expires_at') }}</label>
                    <input class="form-control" type="date" id="hc-doc-expires" name="expires_at" value="{{ old('expires_at') }}">
                </div>
                <div class="hc-field">
                    <label for="hc-doc-front">{{ $c('documents.front') }} *</label>
                    <input class="form-control" type="file" id="hc-doc-front" name="front" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="hc-field">
                    <label for="hc-doc-back">{{ $c('documents.back') }}</label>
                    <input class="form-control" type="file" id="hc-doc-back" name="back" accept=".jpg,.jpeg,.png,.pdf">
                </div>
            </div>
            <div class="hc-actions"><button class="hc-btn is-primary"><i class="fa fa-upload"></i> {{ $c('documents.save') }}</button></div>
        </fieldset>
    </form>
@endif

@if($documents->isEmpty())
    <div class="hc-empty">{{ $c('documents.empty') }}</div>
@else
    <div class="hc-docs">
        @foreach($documents as $document)
            <div class="hc-doc">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <strong><i class="fa fa-id-card"></i> {{ $c('identity_types.'.$document->doc_type) }} <span dir="ltr">{{ $document->number }}</span></strong>
                    <span class="hc-badge" style="{{ $statusStyle[$document->status] ?? '' }}">{{ $c('documents.statuses.'.$document->status) }}</span>
                </div>
                <div class="hc-doc-images">
                    @foreach(['front' => $document->front_path, 'back' => $document->back_path] as $side => $path)
                        @if($path)
                            @php $url = route('admin.hourly-customers.documents.file', [$customer->id, $document->id, $side]); @endphp
                            <a href="{{ $url }}" target="_blank" rel="noopener" title="{{ $c('documents.'.$side) }}">
                                @if($isImage($path))<img src="{{ $url }}" alt="{{ $c('documents.'.$side) }}" loading="lazy">@else<span><i class="fa fa-file-pdf fa-2x"></i><br>{{ $c('documents.'.$side) }}</span>@endif
                            </a>
                        @else
                            <a aria-disabled="true"><span>{{ $c('documents.'.$side) }} —</span></a>
                        @endif
                    @endforeach
                </div>
                <div class="hc-muted small">
                    {{ $c('documents.expires_at') }}: <b>{{ $document->expires_at?->format('d.m.Y') ?? '—' }}</b>
                    @if($document->expired())<span class="hc-badge is-blocked">{{ $c('documents.expired') }}</span>@endif
                    <br>{{ $c('documents.uploaded', ['date' => $document->created_at?->format('d.m.Y H:i')]) }}
                    @if($document->reviewed_by) · {{ $c('documents.reviewed', ['by' => $people['admin'][$document->reviewed_by] ?? '#'.$document->reviewed_by]) }}@endif
                    @if($document->note)<br><i class="fa fa-comment"></i> {{ $document->note }}@endif
                </div>
                @if($canEdit)
                    <form method="POST" action="{{ route('admin.hourly-customers.documents.review', [$customer->id, $document->id]) }}" class="d-flex gap-2 flex-wrap align-items-center">
                        @csrf @method('PUT')
                        <input class="form-control form-control-sm" name="note" maxlength="500" placeholder="{{ $c('documents.note') }}" aria-label="{{ $c('documents.note') }}" style="flex:1 1 140px">
                        @if($document->status !== 'verified')<button class="hc-btn is-green is-sm" name="status" value="verified"><i class="fa fa-check"></i> {{ $c('documents.verify') }}</button>@endif
                        @if($document->status !== 'rejected')<button class="hc-btn is-danger is-sm" name="status" value="rejected"><i class="fa fa-xmark"></i> {{ $c('documents.reject') }}</button>@endif
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endif
