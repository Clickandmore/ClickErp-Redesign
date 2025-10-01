@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.edit Shelter contract")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_CONTROL))

    <form class="row g-3 contractForm" method="post" action="{{route("ShelterContract.update", $contract->id)}}">
        @csrf
        @method('PUT')
        <div class="row p-3">

            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.contracts")}}</label>
                <input type="text" class="form-control" value="{{ $contract->desc }}" readonly>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.shelter-places")}}</label>
                <select name="shelter_id" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @foreach(($shelters ?? []) as $shelter)
                    <option value="{{$shelter->id}}" @if($contract->info && $contract->info->shelter_id == $shelter->id) selected @endif>
                        {{$shelter->title }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">مصدر الاستلام</label>
                <select name="receipt_source" class="form-select form-control select2" data-validation="required">
                    @forelse (__('shelter_worker_statuses.received_by') ?? [] as $k => $name)
                    <option value="{{$k}}" @if (@$contract->info->receipt_source == $k) selected @endif>
                        {{ $name }}
                    </option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.maid date back")}}</label>
                <input type="date" name="maid_date_back" class="form-control" value="{{ $contract->info->maid_date_back ?? '' }}" data-validation="required">
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.arrival_date")}}</label>
                <input type="date" name="arrival_date" id="arrival_date" class="form-control" value="{{ $contract->info->arrival_date ?? '' }}" data-validation="required">
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.salary_final_status")}}</label>
                <select name="salary_final_status" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    <option value="received" @if($contract->info && $contract->info->salary_final_status == 'received') selected @endif>تم استلام الراتب</option>
                    <option value="not_received" @if($contract->info && $contract->info->salary_final_status == 'not_received') selected @endif>لم يتم استلام الراتب</option>
                </select>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.duration_from_enter")}}</label>
                <input type="text" name="duration_from_enter" class="form-control" value="{{ $contract->info->duration_from_enter ?? '' }}" readonly>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.work_willingness")}}</label>
                <select name="wants_to_work" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    <option value="yes" @if($contract->info && $contract->info->wants_to_work == 'yes') selected @endif>{{helperTrans('ingaz.work_willingness_yes')}}</option>
                    <option value="no" @if($contract->info && $contract->info->wants_to_work == 'no') selected @endif>{{helperTrans('ingaz.work_willingness_no')}}</option>
                    <option value="unknown" @if($contract->info && $contract->info->wants_to_work == 'unknown') selected @endif>{{helperTrans('ingaz.work_willingness_unknown')}}</option>
                </select>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">متاحة للاستلام الفوري</label>
                <select name="is_available_for_instant" class="form-select form-control select2" data-validation="required" id="is_available_for_instant">
                    <option value="0" @if($contract->info && $contract->info->is_available_for_instant == false) selected @endif>غير متاحة</option>
                    <option value="1" @if($contract->info && $contract->info->is_available_for_instant == true) selected @endif>متاحة</option>
                </select>
                <small class="form-text text-muted">متاح فقط للعاملات التي ترغب بالعمل</small>
            </div>

            <div class="col-12 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.back_reasons")}}</label>
                <textarea name="back_reasons" rows="4" class="form-control" data-validation="required">{{ $contract->info->back_reasons ?? '' }}</textarea>
            </div>

        </div>

        <div class="col-12 modal-footer">
            <button class="btn btn-primary me-2" type="submit">{{__('buttons.save')}}</button>
            <a href="{{ route('ShelterContract.index') }}" class="btn btn-secondary">{{__('buttons.cancel')}}</a>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')
<script>
    $('.select2').select2({
        placeholder: 'Search for an option...',
        allowClear: true
    });
</script>

<script>
    var indexRoute = "{{ route('ShelterContract.index') }}";
</script>

<script>
    // حساب المدة عند تغيير تاريخ الوصول
    $('#arrival_date').on('change', function() {
        calculateDurationFromArrivalDate();
    });

    function calculateDurationFromArrivalDate() {
        let arrivalDate = $('#arrival_date').val();
        if (arrivalDate) {
            let arrivalDateObj = new Date(arrivalDate);
            let today = new Date();
            let diffTime = Math.abs(today - arrivalDateObj);
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            let durationText = diffDays + ' يوم';
            $('input[name="duration_from_enter"]').val(durationText);
            console.log('Manual arrival date calculation:', arrivalDate, 'Duration:', durationText);
        } else {
            $('input[name="duration_from_enter"]').val('');
        }
    }

    // حساب المدة عند تحميل الصفحة
    $(document).ready(function() {
        if ($('#arrival_date').val()) {
            calculateDurationFromArrivalDate();
        }

        // تعطيل/تمكين حقل الاستلام الفوري حسب رغبة العمل
        toggleInstantReceiptField();
    });

    // تعطيل/تمكين حقل الاستلام الفوري حسب رغبة العمل
    function toggleInstantReceiptField() {
        const wantsToWork = $('select[name="wants_to_work"]').val();
        const instantReceiptField = $('#is_available_for_instant');

        if (wantsToWork === 'yes') {
            instantReceiptField.prop('disabled', false);
            instantReceiptField.closest('.col-4').find('.form-text').removeClass('text-muted').addClass('text-success').text('متاح للعاملات التي ترغب بالعمل');
        } else {
            instantReceiptField.prop('disabled', true);
            instantReceiptField.val('0');
            instantReceiptField.closest('.col-4').find('.form-text').removeClass('text-success').addClass('text-muted').text('متاح فقط للعاملات التي ترغب بالعمل');
        }
    }

    // تغيير حالة حقل الاستلام الفوري عند تغيير رغبة العمل
    $('select[name="wants_to_work"]').on('change', function() {
        toggleInstantReceiptField();
    });
</script>

<script>
    $(document).on('submit', '.contractForm', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        let url = $(this).attr('action');
        let submitButton = $(e.originalEvent.submitter);
        let buttonText = submitButton.text();

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('#loading-wrapper').fadeIn();
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText)
            },
            complete: function () {
                $('#loading-wrapper').fadeOut();
            },
            success: function (result) {
                window.location.href = indexRoute;
                submitButton.html(buttonText)
            },
            error: function (errorObj, errorText, errorThrown) {
                handleErrorResponce(errorObj);
            }
        });
    });
</script>
@endsection
