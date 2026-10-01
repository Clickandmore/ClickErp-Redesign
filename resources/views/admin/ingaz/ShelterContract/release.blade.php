@extends('admin.layout.indexs.index')
@section('page-title', 'تأكيد خروج العاملة ونموذج التسليم')
@section('content')
<div class="card"><div class="card-body">
    <h4>{{ $actionLabel }} — عقد #{{ $contract->id }}</h4>
    <p>استكمل بيانات التسليم ثم أكد الإجراء. تُحدّث بيانات العقد وتتاح الطباعة بالقالب الحالي. يمكنك رفع النموذج الموقّع لاحقًا ضمن مرفقات العقد.</p>
    <p class="text-muted">القالب: {{ $configuration->template?->name }}</p>
    <div class="alert alert-danger d-none" id="release-errors" role="alert"></div>
    <form id="shelter-release-form" action="{{ $submitUrl }}" method="post">
        @csrf
        <input type="hidden" name="shelter_release_version" value="{{ $releaseVersion }}">
        @if ($action === 'deportation')<input type="hidden" name="complete_deportation" value="1">@endif
        <div class="row g-3">
            @foreach (\App\Support\ShelterDocumentFields::fields('release') as $key => $field)
                <div class="{{ in_array($field[1], ['choices', 'textarea']) ? 'col-12' : 'col-md-6' }}">
                    <label class="form-label" for="release-{{ $key }}">{{ $field[0] }}</label>
                    @if ($field[1] === 'choices')
                        <div>
                            @foreach ($field[2] as $value => $label)
                                <label class="me-3"><input type="checkbox" name="shelter_release[{{ $key }}][]" value="{{ $value }}" {{ in_array($value, $values[$key] ?? [], true) ? 'checked' : '' }}> {{ $label }}</label>
                            @endforeach
                        </div>
                    @elseif ($field[1] === 'textarea')
                        <textarea id="release-{{ $key }}" class="form-control" name="shelter_release[{{ $key }}]" rows="2" maxlength="5000">{{ $values[$key] ?? '' }}</textarea>
                    @else
                        <input id="release-{{ $key }}" class="form-control" type="{{ $field[1] }}" name="shelter_release[{{ $key }}]" value="{{ $values[$key] ?? '' }}" maxlength="255" {{ in_array($key, ['worker_name', 'date', 'exit_time', 'recipient_name', 'recipient_id', 'recipient_phone', 'recipient_capacity', 'employee_name']) ? 'required' : '' }}>
                    @endif
                </div>
            @endforeach
            @if ($action === 'back')
                <label class="col-12">سبب تصفية رصيد الإيواء (عند وجود رصيد، وبعد مراجعة المحاسبة)
                    <input class="form-control" name="write_off_reason" maxlength="1000">
                </label>
            @endif
        </div>
        <button type="submit" class="btn btn-primary mt-4">تأكيد الخروج وحفظ البيانات</button>
        <a class="btn btn-outline-secondary mt-4" href="{{ route('ShelterContract.show', $contract->id) }}">إلغاء</a>
    </form>
</div></div>
@endsection
@section('js')
<script>
$('#shelter-release-form').on('submit', function (event) {
    event.preventDefault();
    const form = this;
    $(form).find('button[type="submit"]').prop('disabled', true);
    $('#release-errors').addClass('d-none').text('');
    $.ajax({url: form.action, method: 'POST', data: $(form).serialize(),
        success: function (response) {
            if (response.success === false) {
                $('#release-errors').removeClass('d-none').text(response.message || 'تعذر حفظ الإجراء.');
                return;
            }
            window.location.href = @json(route('ShelterContract.show', $contract->id));
        },
        error: function (xhr) {
            const response = xhr.responseJSON || {};
            const errors = response.errors ? Object.values(response.errors).flat().join('\n') : response.message;
            $('#release-errors').removeClass('d-none').text(errors || 'تعذر تأكيد الخروج. لم يتم حفظ النموذج؛ راجع البيانات وحاول مرة أخرى.');
        },
        complete: function () { $(form).find('button[type="submit"]').prop('disabled', false); }
    });
});
</script>
@endsection
