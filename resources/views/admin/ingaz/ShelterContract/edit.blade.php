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

            <div class="col-12 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.back_reasons")}}</label>
                <textarea name="back_reasons" rows="4" class="form-control" data-validation="required">{{ $contract->info->back_reasons ?? '' }}</textarea>
            </div>

        </div>

        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__('buttons.save')}}</button>
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
