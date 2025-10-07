@extends('admin.layout.indexs.index')

@section('title', 'إدارة قوالب الرسائل النصية')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-sms me-2"></i>
                            إدارة قوالب الرسائل النصية
                        </h3>
                        <a href="{{ route('admin.sms-templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            إضافة قالب جديد
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>اسم القالب</th>
                                    <th>العنوان</th>
                                    <th>الرسالة</th>
                                    <th>المتغيرات</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $template)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <code>{{ $template->name }}</code>
                                    </td>
                                    <td>{{ $template->title }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="{{ $template->message }}">
                                            {{ $template->message }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($template->variables)
                                            @foreach($template->variables as $variable)
                                                <span class="badge bg-info me-1">{{ $variable }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">لا توجد متغيرات</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->is_active)
                                            <span class="badge bg-success">نشط</span>
                                        @else
                                            <span class="badge bg-danger">غير نشط</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.sms-templates.show', $template) }}"
                                               class="btn btn-sm btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.sms-templates.edit', $template) }}"
                                               class="btn btn-sm btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success"
                                                    onclick="testTemplate({{ $template->id }})" title="اختبار">
                                                <i class="fas fa-vial"></i>
                                            </button>
                                            <form action="{{ route('admin.sms-templates.destroy', $template) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('هل أنت متأكد من الحذف؟')" title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <br>
                                        لا توجد قوالب رسائل
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>


                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal اختبار القالب -->
<div class="modal fade" id="testTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">اختبار قالب الرسالة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testTemplateForm">
                    <div id="variablesContainer"></div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">الرسالة النهائية:</label>
                        <div class="alert alert-info" id="processedMessage"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" onclick="processTemplate()">معالجة الرسالة</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentTemplateId = null;

function testTemplate(templateId) {
    currentTemplateId = templateId;

    // جلب بيانات القالب
    fetch(`/admin/sms-templates/${templateId}`)
        .then(response => response.json())
        .then(data => {
            const template = data.template;
            const variablesContainer = document.getElementById('variablesContainer');

            // إنشاء حقول للمتغيرات
            variablesContainer.innerHTML = '';
            if (template.variables && template.variables.length > 0) {
                template.variables.forEach(variable => {
                    const div = document.createElement('div');
                    div.className = 'mb-3';
                    div.innerHTML = `
                        <label class="form-label">${variable}:</label>
                        <input type="text" class="form-control" name="${variable}"
                               placeholder="أدخل قيمة ${variable}">
                    `;
                    variablesContainer.appendChild(div);
                });
            } else {
                variablesContainer.innerHTML = '<p class="text-muted">لا توجد متغيرات في هذا القالب</p>';
            }

            // عرض الرسالة الأصلية
            document.getElementById('processedMessage').textContent = template.message;

            // عرض المودال
            new bootstrap.Modal(document.getElementById('testTemplateModal')).show();
        });
}

function processTemplate() {
    const form = document.getElementById('testTemplateForm');
    const formData = new FormData(form);
    const variables = {};

    for (let [key, value] of formData.entries()) {
        variables[key] = value;
    }

    fetch(`/admin/sms-templates/${currentTemplateId}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            test_variables: variables
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('processedMessage').textContent = data.message;
        }
    });
}
</script>
@endsection
