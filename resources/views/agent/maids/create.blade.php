@extends('agent.layout')

@php $isAr = app()->getLocale() === 'ar'; @endphp

@section('page-title', $isAr ? 'إضافة سيرة ذاتية' : 'Add CV')

@section('content')
    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'إضافة عاملة جديدة' : 'Add New Worker' }}</h3>
            <p class="text-muted">{{ $isAr ? 'يمكنك رفع ملف PDF لاستخراج البيانات تلقائياً' : 'You can upload a PDF to extract data automatically' }}</p>
        </div>

        <div class="agent-card mb-4" style="border: 2px dashed var(--agent-line); background: rgba(0,0,0,0.02);">
            <div class="card-body text-center p-5">
                <i class="fa fa-file-pdf fa-3x mb-3 text-danger"></i>
                <h5>{{ $isAr ? 'استخراج البيانات من PDF' : 'Extract Data from PDF' }}</h5>
                <input type="file" id="cv_pdf" accept=".pdf" class="d-none">
                <button type="button" class="agent-button" onclick="document.getElementById('cv_pdf').click()">
                    <i class="fa fa-upload"></i>
                    <span>{{ $isAr ? 'اختر ملف PDF' : 'Select PDF File' }}</span>
                </button>
                <div id="extraction_status" class="mt-3 small fw-bold text-primary"></div>
            </div>
        </div>

        <form action="{{ route('agent.maids.store') }}" method="POST" id="maid_form">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'اسم العاملة' : 'Worker Name' }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="field_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'رقم الجواز' : 'Passport Number' }} <span class="text-danger">*</span></label>
                        <input type="text" name="passport_num" id="field_passport" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'الجنسية' : 'Nationality' }} <span class="text-danger">*</span></label>
                        <select name="nationality_id" id="field_nationality" class="form-select" required>
                            <option value="">{{ $isAr ? 'اختر الجنسية' : 'Select Nationality' }}</option>
                            @foreach ($nationalities as $nat)
                                <option value="{{ $nat->id }}">{{ $nat->sub_title ?: $nat->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'مكتب الاستقدام' : 'Recruitment Office' }} <span class="text-danger">*</span></label>
                        <select name="office_id" class="form-select" required>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}">{{ $office->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'العمر' : 'Age' }}</label>
                        <input type="number" name="age" id="field_age" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'الديانة' : 'Religion' }}</label>
                        <select name="religion_id" id="field_religion" class="form-select">
                            <option value="">{{ $isAr ? 'اختر الديانة' : 'Select Religion' }}</option>
                            @foreach ($religions as $rel)
                                <option value="{{ $rel->id }}">{{ $rel->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'سنوات الخبرة' : 'Experience Years' }}</label>
                        <input type="number" name="experience_years" id="field_experience" class="form-control">
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="agent-form-group">
                        <label>{{ $isAr ? 'نوع العمل' : 'Job Types' }}</label>
                        <div class="d-flex flex-wrap gap-3 mt-2">
                            @foreach ($jobTypes as $jt)
                                <label class="d-flex align-items-center gap-2">
                                    <input type="checkbox" name="job_type_ids[]" value="{{ $jt->id }}">
                                    <span>{{ $jt->title }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="agent-button" style="width: 100%; height: 50px;">
                        <i class="fa fa-save"></i>
                        <span>{{ $isAr ? 'حفظ السيرة الذاتية' : 'Save CV' }}</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    {{-- PDF.js for extraction --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        document.getElementById('cv_pdf').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const status = document.getElementById('extraction_status');
            status.textContent = "{{ $isAr ? 'جاري جلب البيانات من الملف...' : 'Extracting data from file...' }}";

            try {
                const reader = new FileReader();
                reader.onload = async function() {
                    const typedarray = new Uint8Array(this.result);
                    const pdf = await pdfjsLib.getDocument(typedarray).promise;
                    let fullText = '';

                    for (let i = 1; i <= pdf.numPages; i++) {
                        const page = await pdf.getPage(i);
                        const textContent = await page.getTextContent();
                        const pageText = textContent.items.map(item => item.str).join(' ');
                        fullText += pageText + '\n';
                    }

                    // Send text to server for "AI" extraction
                    const response = await fetch("{{ route('agent.maids.extract-cv') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ text: fullText })
                    });

                    const data = await response.json();
                    
                    // Fill form
                    if (data.name) document.getElementById('field_name').value = data.name;
                    if (data.passport_num) document.getElementById('field_passport').value = data.passport_num;
                    if (data.nationality_id) document.getElementById('field_nationality').value = data.nationality_id;
                    if (data.age) document.getElementById('field_age').value = data.age;
                    if (data.religion_id) document.getElementById('field_religion').value = data.religion_id;
                    if (data.experience_years) document.getElementById('field_experience').value = data.experience_years;

                    status.textContent = "{{ $isAr ? 'تم استخراج البيانات بنجاح!' : 'Data extracted successfully!' }}";
                    status.style.color = 'green';
                };
                reader.readAsArrayBuffer(file);
            } catch (error) {
                console.error(error);
                status.textContent = "{{ $isAr ? 'فشل استخراج البيانات من الملف.' : 'Failed to extract data from file.' }}";
                status.style.color = 'red';
            }
        });
    </script>
@endsection
