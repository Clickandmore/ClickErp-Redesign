@php
    $documentEditor = app(\App\Services\ShelterDocumentService::class)->editor($editingContract);
@endphp
@if ($documentEditor['templates']->isNotEmpty() || $documentEditor['documents']->isNotEmpty())
    <div id="shelter-custom-documents">
        <div class="sc-subhead">
            <strong>نماذج الدخول والخروج</strong>
            <small>تُحفظ بيانات الاستلام مع العقد. يُختار قالب التسليم المتاح تلقائيًا، وتُستكمل بياناته عند تنفيذ الخروج. يمكنك اختيار قالب آخر هنا.</small>
        </div>
        @foreach (\App\Support\ShelterDocumentFields::kinds() as $kind => $label)
            @php
                $savedDocument = $documentEditor['documents']->get($kind);
                $selectedTemplate = old("shelter_documents.$kind.template_id", $savedDocument?->enabled ? $savedDocument->template_id : ($kind === 'release' ? ($documentEditor['default_release_template_id'] ?? '') : ''));
                $fieldValues = old("shelter_documents.$kind.data", $savedDocument?->data ?? ($documentEditor['defaults'][$kind] ?? []));
                $kindTemplates = $documentEditor['templates']->where('kind', $kind);
                $documentFields = \App\Support\ShelterDocumentFields::fields($kind);
            @endphp
            <div class="sc-doc {{ $selectedTemplate ? 'is-active' : '' }}" data-document-kind="{{ $kind }}">
                <div class="sc-doc__head">
                    <span class="sc-doc__icon"><i class="fas {{ $kind === 'entry' ? 'fa-sign-in-alt' : 'fa-sign-out-alt' }}"></i></span>
                    <div class="sc-doc__info">
                        <strong>{{ $label }}</strong>
                        <small class="sc-doc__state" data-on="مفعّل — سيُطبع بقالب الشركة المختار" data-off="{{ $kind === 'entry' ? 'يُستخدم النموذج الافتراضي' : 'غير مفعّل' }}">{{ $selectedTemplate ? 'مفعّل — سيُطبع بقالب الشركة المختار' : ($kind === 'entry' ? 'يُستخدم النموذج الافتراضي' : 'غير مفعّل') }}</small>
                    </div>
                    <div class="sc-doc__actions">
                        <select id="shelter-template-{{ $kind }}" name="shelter_documents[{{ $kind }}][template_id]" class="form-select form-select-sm shelter-template-select" aria-label="{{ $label }}">
                            <option value="">{{ $kind === 'entry' ? 'النموذج الحالي (الافتراضي)' : 'نموذج التسليم المتاح تلقائيًا' }}</option>
                            @foreach ($kindTemplates as $item)
                                <option value="{{ $item->id }}" {{ (string) $selectedTemplate === (string) $item->id ? 'selected' : '' }}>{{ $item->company_name }} — {{ $item->name }}</option>
                            @endforeach
                            @if ($savedDocument && !$kindTemplates->contains('id', $savedDocument->template_id))
                                <option value="{{ $savedDocument->template_id }}" {{ (string) $selectedTemplate === (string) $savedDocument->template_id ? 'selected' : '' }}>{{ $savedDocument->template?->name }} (القالب المرتبط بالعقد)</option>
                            @endif
                        </select>
                        @if ($savedDocument?->enabled && $kind === 'entry')
                            <a target="_blank" rel="noopener" class="btn btn-sm btn-primary text-nowrap" href="{{ route('shelter_contract_form.download', ['id' => $editingContract->id, 'document' => $kind]) }}"><i class="fas fa-print me-1"></i>طباعة النموذج</a>
                        @endif
                    </div>
                </div>
                @if ($kind === 'release')
                    <p class="p-3 mb-0 text-muted">هذا اختيار للقالب فقط. سيظهر نموذج بيانات المستلم عند تأكيد التسليم أو الخروج، ولن يُنشأ مستند تسليم مسبقًا.</p>
                @endif
                <fieldset class="shelter-document-fields sc-doc__body" @if (!$selectedTemplate || $kind === 'release') hidden disabled @endif>
                    @foreach (\App\Support\ShelterDocumentFields::sections($kind) as $sectionKey => [$sectionTitle, $sectionKeys])
                        @php [$sectionAr, $sectionEn] = \App\Support\ShelterDocumentFields::labelParts($sectionTitle ?: ($sectionKey === 'meta' ? 'بيانات النموذج | Form Details' : 'التوقيعات | Signatures')); @endphp
                        <div class="sc-doc__section">
                            <div class="sc-doc__section-title">{{ $sectionAr }} <span>{{ $sectionEn }}</span></div>
                            <div class="row g-2">
                                @foreach (array_replace(array_flip($sectionKeys), array_intersect_key($documentFields, array_flip($sectionKeys))) as $key => $field)
                                    @php [$fieldAr, $fieldEn] = \App\Support\ShelterDocumentFields::labelParts($field[0]); @endphp
                                    <div class="{{ in_array($field[1], ['choices', 'textarea']) ? 'col-12' : 'col-md-6 col-xl-4' }}">
                                        <label class="form-label sc-label" for="shelter-{{ $kind }}-{{ $key }}">{{ $fieldAr }} <span>{{ $fieldEn }}</span></label>
                                        @if ($field[1] === 'choices')
                                            <div class="sc-chips">
                                                @foreach ($field[2] as $value => $choiceLabel)
                                                    <label class="sc-chip"><input type="checkbox" name="shelter_documents[{{ $kind }}][data][{{ $key }}][]" value="{{ $value }}" {{ in_array($value, $fieldValues[$key] ?? [], true) ? 'checked' : '' }}> <span>{{ \App\Support\ShelterDocumentFields::labelParts($choiceLabel)[0] }}</span></label>
                                                @endforeach
                                            </div>
                                        @elseif ($field[1] === 'textarea')
                                            <textarea id="shelter-{{ $kind }}-{{ $key }}" data-document-field="{{ $key }}" name="shelter_documents[{{ $kind }}][data][{{ $key }}]" class="form-control" rows="2" maxlength="5000">{{ $fieldValues[$key] ?? '' }}</textarea>
                                        @else
                                            <input id="shelter-{{ $kind }}-{{ $key }}" data-document-field="{{ $key }}" type="{{ $field[1] }}" name="shelter_documents[{{ $kind }}][data][{{ $key }}]" class="form-control" maxlength="255" value="{{ $fieldValues[$key] ?? '' }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </fieldset>
            </div>
        @endforeach
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.shelter-template-select').forEach(function (select) {
                select.addEventListener('change', function () {
                    const doc = select.closest('[data-document-kind]');
                    const fields = doc.querySelector('fieldset');
                    const state = doc.querySelector('.sc-doc__state');
                    fields.hidden = !select.value || doc.dataset.documentKind === 'release';
                    fields.disabled = !select.value || doc.dataset.documentKind === 'release';
                    doc.classList.toggle('is-active', !!select.value);
                    state.textContent = select.value ? state.dataset.on : state.dataset.off;
                });
            });
        });
        let shelterDocumentContractId = @json(($isEditMode || old('shelter_documents')) ? (string) $selectedContractId : null);
        window.fillShelterDocumentDefaults = function (defaults, contractId) {
            if (!contractId || shelterDocumentContractId === String(contractId)) return;
            const switching = shelterDocumentContractId !== null;
            shelterDocumentContractId = String(contractId);
            document.querySelectorAll('[data-document-kind]').forEach(function (section) {
                const values = (defaults || {})[section.dataset.documentKind] || {};
                if (switching) {
                    const select = section.querySelector('select');
                    select.value = section.dataset.documentKind === 'release' ? @json((string) ($documentEditor['default_release_template_id'] ?? '')) : '';
                    select.dispatchEvent(new Event('change'));
                }
                section.querySelectorAll('[data-document-field]').forEach(function (input) {
                    input.value = values[input.dataset.documentField] || '';
                });
                section.querySelectorAll('input[type="checkbox"]').forEach(input => input.checked = false);
            });
        };
    </script>
@endif
