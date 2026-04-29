@extends('admin.layout.indexs.index')


@section('page-title')
{{ __('admin.travel') }}
@endsection


@section('css')
<style>
    .travel-form-panel {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        background: #fcfdff;
    }

    .contracts-preview {
        border: 1px dashed #cfd8e3;
        border-radius: 10px;
        background: #fff;
        padding: 12px;
        min-height: 56px;
    }

    .contract-preview-item {
        border: 1px solid #e9eef5;
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 8px;
        background: #f8fbff;
    }

    .contract-preview-item:last-child {
        margin-bottom: 0;
    }

    .travel-default-hint {
        color: #6b7280;
        font-size: 12px;
        margin-top: 6px;
        display: block;
    }

    #order_ids + .select2-container .select2-selection--multiple {
        min-height: 46px;
        border-radius: 10px;
        border: 1px solid #d1d9e6;
        padding: 4px 6px;
    }
</style>
@endsection


@section('content')
@php
    $canManageTravelForm = @$data
        ? @AdminGuardHasPerm(\App\PermKeys::TRAVEL_EDIT)
        : @AdminGuardHasPerm(\App\PermKeys::TRAVEL_CREATE);
@endphp

@if ($canManageTravelForm)

<h1>
    @if (@$data)
    {{ __('admin.travel-edit') }}
    @else
    {{ __('admin.travel-add') }}
    @endif
</h1>
<hr>

<div class="">
    @if (session('error'))
    <div class="alert alert-danger mt-5 card-body">
        {{ session('error') }}
    </div>
    @endif

    @if (session('success'))
    <div class="alert alert-success mt-5 card-body">
        {{ session('success') }}
    </div>
    @endif

    @if (session('status'))
    <div class="alert alert-warning mt-5 card-body">
        {{ session('status') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger card-body">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>

<form class="row" method="POST" @if (@$data) action="{{ route('travel.update', $data->id) }}" @else action='{{ route('travel.store') }}' @endif enctype="multipart/form-data">

    @if (@$data)
    {{-- @method('PATCH') --}}
    @endif
    @csrf

    <input type="hidden" name="id" value="{{ @$data->id }}">

    {{-- إضافة الحقول المطلوبة كحقول مخفية --}}
    <input type="hidden" name="user_id" id="user_id" value="{{ @$data->user_id ?? @$userId ?? '' }}">
    <input type="hidden" name="maid_id" id="maid_id" value="{{ @$data->maid_id ?? @$maidId ?? '' }}">

    @php
        $selectedContracts = old('order_ids', @$selectedOrderIds ? collect($selectedOrderIds)->all() : []);
        if (empty($selectedContracts) && old('order_id')) {
            $selectedContracts = [old('order_id')];
        }
        if (empty($selectedContracts) && @$orderId) {
            $selectedContracts = [@$orderId];
        }
        $selectedTravelType = old('travel_type') ?: (@$data->travel_type ?: 'international');
        $selectedTravelWith = 'airport';
        $selectedAssignedTo = old('assigned_to') ?: (@$data->assigned_to ?? '');
    @endphp

    {{-- اختيار العقود (يدعم متعدد) --}}
    <div class="mb-4 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('ingaz.contract') }}</label>
        <div class="col-sm-7">
            <div class="travel-form-panel">
                <select name="order_ids[]" id="order_ids" class="form-control cu-form-design select2" multiple
                    data-placeholder="@lang('ingaz.select_contract')">
                    @forelse ($contracts as $contract)
                        <option value="{{$contract->id}}"
                            @if (in_array($contract->id, $selectedContracts))
                                selected
                            @endif>
                            عقد #{{$contract->id}} | العميل: {{@$contract->user->name ?? 'غير محدد'}} | العاملة: {{@$contract->maid->name ?? 'غير محدد'}}
                            @if(@$contract->info->msaned_number || @$contract->msaned_number) | مساند: {{@$contract->info->msaned_number ?: @$contract->msaned_number}} @endif
                            @if(@$contract->user->national_num) | الهوية: {{@$contract->user->national_num}} @endif
                            @if(@$contract->info->visa_number) | تأشيرة: {{@$contract->info->visa_number}} @endif
                        </option>
                    @empty
                    @endforelse
                </select>
                <small class="text-muted d-block mt-2">يمكن البحث برقم العقد أو رقم عقد مساند أو هوية العميل، ويمكن اختيار أكثر من عقد لنفس الرحلة.</small>

                <div id="selected_contracts_preview" class="contracts-preview mt-2">
                    <span class="text-muted">لم يتم اختيار عقود بعد.</span>
                </div>

                <div>
                    @error('order_ids')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                    @error('order_ids.*')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                    @error('order_id')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- حذف خانة العميل والعاملة --}}
    {{-- <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.client_id') }}</label>
        <div class="col-sm-5">
            <select name="user_id" id="user_id" class="form-control cu-form-design select2">
                <option value="">@lang('admin.select_client')</option>
                @forelse (@$clientsList ?? [] as $id => $name)
                <option value="{{$id}}"
                    @if ((@$data && @$data->user_id == $id) ||
                        @$userId == $id ||
                        (@$order && @$order->user_id == $id))
                        selected
                    @endif>
                    {{ $name }}
                </option>
                @empty
                @endforelse
            </select>
            <div>
                @error('user_id')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.maid_id') }}</label>
        <div class="col-sm-5">
            <select name="maid_id" id="maid_id" class="form-control cu-form-design select2">
                <option value="">@lang('admin.select_maid')</option>
                @forelse (@$maidsList ?? [] as $id => $name)
                <option value="{{$id}}"
                    @if ((@$data && @$data->maid_id == $id) ||
                        @$maidId == $id ||
                        (@$order && @$order->maid_id == $id))
                        selected
                    @endif>
                    {{ $name }}
                </option>
                @empty
                @endforelse
            </select>
            <div>
                @error('maid_id')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div> --}}

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.travel_type') }}</label>
        <div class="col-sm-5">
            <select name="travel_type" id="travel_type" class="form-control cu-form-design select2">
                <option value="international" @if ($selectedTravelType == 'international') selected @endif>رحلة دولية</option>
                <option value="domestic" @if ($selectedTravelType == 'domestic') selected @endif>رحلة داخلية</option>
            </select>
            <span class="travel-default-hint">الاختيار الافتراضي: رحلة دولية.</span>
            <div>
                @error('travel_type')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- عرض مدن تسليم العملاء --}}
    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">مدن تسليم العملاء</label>
        <div class="col-sm-5">
            <textarea class="form-control" id="client_city" rows="2" readonly placeholder="سيتم ملؤها تلقائياً عند اختيار العقود"></textarea>
        </div>
    </div>

    <input type="hidden" name="travel_with" id="travel_with" value="airport">

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.arrival_airport') }}</label>
        <div class="col-sm-5">
            <select name="arrival_airport" id="arrival_airport" class="form-control cu-form-design select2">
                <option value="">اختر مطار الوصول</option>
                <optgroup>
                    <option value="RUH" @if (@$data->arrival_airport == 'RUH') selected @endif>مطار الملك خالد الدولي - الرياض (RUH)</option>
                    <option value="JED" @if (@$data->arrival_airport == 'JED') selected @endif>مطار الملك عبدالعزيز الدولي - جدة (JED)</option>
                    <option value="DMM" @if (@$data->arrival_airport == 'DMM') selected @endif>مطار الملك فهد الدولي - الدمام (DMM)</option>
                    <option value="MED" @if (@$data->arrival_airport == 'MED') selected @endif>مطار الأمير محمد بن عبدالعزيز الدولي - المدينة المنورة (MED)</option>
                    <option value="TUU" @if (@$data->arrival_airport == 'TUU') selected @endif>مطار تبوك الدولي - تبوك (TUU)</option>
                    <option value="AHB" @if (@$data->arrival_airport == 'AHB') selected @endif>مطار أبها الإقليمي - أبها (AHB)</option>
                    <option value="TIF" @if (@$data->arrival_airport == 'TIF') selected @endif>مطار الطائف الإقليمي - الطائف (TIF)</option>
                    <option value="ELQ" @if (@$data->arrival_airport == 'ELQ') selected @endif>مطار القصيم الإقليمي - بريدة (ELQ)</option>
                    <option value="URY" @if (@$data->arrival_airport == 'URY') selected @endif>مطار الجوف الإقليمي - سكاكا (URY)</option>
                    <option value="HAS" @if (@$data->arrival_airport == 'HAS') selected @endif>مطار حائل الإقليمي - حائل (HAS)</option>
                    <option value="BHH" @if (@$data->arrival_airport == 'BHH') selected @endif>مطار الباحة الإقليمي - الباحة (BHH)</option>
                    <option value="GIZ" @if (@$data->arrival_airport == 'GIZ') selected @endif>مطار جازان الإقليمي - جازان (GIZ)</option>
                    <option value="URY" @if (@$data->arrival_airport == 'URY') selected @endif>مطار نجران الإقليمي - نجران (URY)</option>
                    <option value="URY" @if (@$data->arrival_airport == 'URY') selected @endif>مطار القريات الإقليمي - القريات (URY)</option>
                </optgroup>
            </select>
            <div>
                @error('arrival_airport')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- تحديث العناوين --}}
    <div class="mb-3 row">
        <label for="arrival_date" class="col-sm-3 col-form-label">تاريخ الوصول</label>
        <div class="col-sm-5">
            <input type="date" name="arrival_date" id="arrival_date" value="{{ old('arrival_date', @$data->arrival_date) }}"
                class="form-control">
            <div>
                @error('arrival_date')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="arrival_time" class="col-sm-3 col-form-label">وقت الوصول </label>
        <div class="col-sm-5">
            <input type="time" name="arrival_time" id="arrival_time" value="{{ old('arrival_time', @$data->arrival_time) }}"
                class="form-control">
            <div>
                @error('arrival_time')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="flight_company" class="col-sm-3 col-form-label">{{ __('admin.flight_company') }}</label>
        <div class="col-sm-5">
            <input type="text" name="flight_company" id="flight_company" value="{{ old('flight_company', @$data->flight_company) }}"
                class="form-control" placeholder="اسم شركة الطيران">
            <div>
                @error('flight_company')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="flight_number" class="col-sm-3 col-form-label">{{ __('admin.flight_number') }}</label>
        <div class="col-sm-5">
            <input type="text" name="flight_number" id="flight_number" value="{{ old('flight_number', @$data->flight_number) }}"
                class="form-control" placeholder="رقم الرحلة">
            <div>
                @error('flight_number')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="notes" class="col-sm-3 col-form-label">{{ __('admin.notes') }}</label>
        <div class="col-sm-5">
            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="ملاحظات إضافية">{{ old('notes', @$data->notes) }}</textarea>
            <div>
                @error('notes')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="assigned_to" class="col-sm-3 col-form-label">إسناد الرحلة إلى سائق / موظف</label>
        <div class="col-sm-5">
            <select name="assigned_to" id="assigned_to" class="form-control cu-form-design select2">
                <option value="">بدون إسناد الآن</option>
                @foreach (($assignableStaff ?? []) as $staff)
                    <option value="{{ $staff['id'] }}" @if ((string) $selectedAssignedTo === (string) $staff['id']) selected @endif>
                        {{ $staff['label'] }}
                        @if (!empty($staff['phone'])) | {{ $staff['phone'] }} @endif
                    </option>
                @endforeach
            </select>
            <span class="travel-default-hint">عند اختيار سائق/موظف سيتم إرسال إشعار له بتفاصيل الرحلة ورابط إكمالها بعد استلام العاملات.</span>
            <div>
                @error('assigned_to')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="assignment_note" class="col-sm-3 col-form-label">ملاحظة الإسناد</label>
        <div class="col-sm-5">
            <textarea name="assignment_note" id="assignment_note" rows="2" class="form-control" placeholder="ملاحظة اختيارية للسائق">{{ old('assignment_note', @$data->assignment_note) }}</textarea>
            <div>
                @error('assignment_note')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- حذف تكرار مطار وتاريخ المغادرة --}}
    {{-- <div class="mb-3 row">
        <label for="departure_airport" class="col-sm-3 col-form-label">مطار المغادرة</label>
        <div class="col-sm-5">
            <input type="text" name="departure_airport" id="departure_airport" value="{{ old('departure_airport', @$data->departure_airport) }}"
                class="form-control" placeholder="مطار المغادرة">
            <div>
                @error('departure_airport')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="departure_date" class="col-sm-3 col-form-label">تاريخ المغادرة</label>
        <div class="col-sm-5">
            <input type="date" name="departure_date" id="departure_date" value="{{ old('departure_date', @$data->departure_date) }}"
                class="form-control">
            <div>
                @error('departure_date')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div> --}}

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.files') }}</label>
        <div class="col-sm-5">
            <input type="file" multiple class="form-control" id="files" name="files[]">
            <div class="error-files"></div>
        </div>
    </div>


    <div class="mb-3 row">
        <div class="mt-10 col-sm-12 text-end">
            <a href="{{ route('travel') }}" class="btn btn-danger">{{ __('admin.back') }}</a>
            <button type="submit" class="btn btn-success">{{ __('admin.confirm') }}</button>
        </div>
    </div>

</form>


@if (@$data)
<div class="d-flex justify-content-between align-items-center">
    <!-- Title on the right -->
    <h5 class="mb-4">
        {{ __('admin.current_uploaded_files') }}
    </h5>
</div>

<div class="row">
    <div class="text-center col-md-12 table-responsive">
        <table class="table border table-striped table-row-bordered table-rounded cu-table-adj">
            <thead>
                <tr class="text-center">
                    <th>{{ __('admin.filename') }}</th>
                    <th>{{ __('admin.uploaded_at') }}</th>
                    <th style="width:15%">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse (@$data->files ?? [] as $k => $fileRow)
                <tr>
                    <td>{{ @$fileRow->file }}</td>
                    <td>{{ @$fileRow->created_at->format('F j, Y H:i:s') }}</td>
                    <td>
                        <div class="d-flex text-center" role="">
                            <a href="{{ route('travel.file.download', ['id' => $fileRow->id]) }}"
                                class="btn btn-sm btn-success m-2">
                                {{ __('admin.download') }}
                            </a>
                            @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_DELETE))
                            <button class="btn btn-sm btn-danger m-2 delete-file" data-id="{{ $fileRow->id }}">
                                حذف الملف
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">{{ __('admin.no_files_found_currently') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@else
    @include('components.no_permissions')
@endif


@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    const contracts = @json($contracts);
    const initialSelectedOrderIds = @json(array_values(array_map('strval', $selectedContracts ?? [])));
    const contractIdFromUrl = "{{ request()->contract_id }}";

    $('.select2').select2({
        width: '100%'
    });

    $('#order_ids').select2({
        width: '100%',
        placeholder: 'ابحث برقم العقد / رقم مساند / هوية العميل'
    });

    if (contractIdFromUrl && !initialSelectedOrderIds.includes(contractIdFromUrl)) {
        initialSelectedOrderIds.push(contractIdFromUrl);
    }

    if (initialSelectedOrderIds.length > 0) {
        $('#order_ids').val(initialSelectedOrderIds).trigger('change');
    }

    if (!$('#travel_type').val()) {
        $('#travel_type').val('international').trigger('change');
    }

    if (!$('#travel_with').val()) {
        $('#travel_with').val('airport').trigger('change');
    }

    function buildSelectedContractsPreview(selectedContracts) {
        if (!selectedContracts.length) {
            $('#selected_contracts_preview').html('<span class="text-muted">لم يتم اختيار عقود بعد.</span>');
            return;
        }

        const visibleContracts = selectedContracts.slice(0, 6);
        let html = visibleContracts.map(function(contract) {
            const client = contract.user && contract.user.name ? contract.user.name : 'غير محدد';
            const maid = contract.maid && contract.maid.name ? contract.maid.name : 'غير محدد';
            const city = contract.user && contract.user.city ? contract.user.city : 'غير محدد';
            const visa = contract.info && contract.info.visa_number ? contract.info.visa_number : '-';
            const musaned = contract.info && contract.info.msaned_number ? contract.info.msaned_number : (contract.msaned_number || '-');
            const nationalId = contract.user && contract.user.national_num ? contract.user.national_num : '-';
            return `
                <div class="contract-preview-item">
                    <div><strong>عقد #${contract.id}</strong> | عميلة: ${client}</div>
                    <div class="text-muted small">العاملة: ${maid} | مدينة التسليم: ${city} | مساند: ${musaned} | الهوية: ${nationalId} | التأشيرة: ${visa}</div>
                </div>
            `;
        }).join('');

        if (selectedContracts.length > visibleContracts.length) {
            html += `<div class="text-muted small">+ ${selectedContracts.length - visibleContracts.length} عقود إضافية</div>`;
        }

        $('#selected_contracts_preview').html(html);
    }

    function updateDerivedFields() {
        const selectedIds = $('#order_ids').val() || [];
        const selectedContracts = contracts.filter(function(contract) {
            return selectedIds.includes(String(contract.id));
        });

        if (selectedContracts.length === 0) {
            $('#user_id').val('');
            $('#maid_id').val('');
            $('#client_city').val('');
            buildSelectedContractsPreview([]);
            return;
        }

        const firstContract = selectedContracts[0];
        $('#user_id').val(firstContract.user_id || '');
        $('#maid_id').val(firstContract.maid_id || '');

        const cities = [...new Set(selectedContracts
            .map(function(contract) {
                return contract.user && contract.user.city ? contract.user.city : null;
            })
            .filter(function(city) {
                return !!city;
            })
        )];

        if (cities.length > 0) {
            $('#client_city').val(cities.join('\n'));
        } else {
            $('#client_city').val('غير محدد');
        }

        buildSelectedContractsPreview(selectedContracts);
    }

    $('#order_ids').on('change', updateDerivedFields);
    updateDerivedFields();

    $('.delete-file').click(function(event) {
        event.preventDefault();

        var fileId = $(this).data('id');

        swal({
            title: '{{ __('admin.confirm_delete') }}',
            icon: 'warning',
            buttons: {
                cancel: '{{ __('admin.no') }}',
                delete: '{{ __('admin.yes') }}'
            },
            dangerMode: true
        }).then(function(willDelete) {
            if (willDelete) {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: '{{ url('ar/admin/travel/file/delete') }}' + '/' + fileId,
                    type: 'DELETE',
                    dataType: 'json',
                    data: {
                        _token: csrfToken
                    },
                    success: function(response) {
                        swal('{{ __('admin.success') }}', response.message, 'success')
                            .then(function() {
                                $('.delete-file[data-id="' + fileId + '"]').closest('tr').remove();

                                if (response.no_files) {
                                    $('tbody').html(
                                        '<tr><td colspan="4">{{ __('admin.no_files_found_currently') }}</td></tr>'
                                    );
                                }
                            });
                    },
                    error: function(xhr) {
                        var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : null;
                        if (errorMessage) {
                            swal('{{ __('admin.error') }}', errorMessage, 'error');
                        } else {
                            swal('{{ __('admin.error') }}', '{{ __('admin.unknown_error') }}', 'error');
                        }
                    }
                });
            }
        });
    });

    $('form').on('submit', function(e) {
        var orderIds = $('#order_ids').val() || [];

        if (orderIds.length === 0) {
            e.preventDefault();
            swal('{{ __('admin.error') }}', 'يجب اختيار عقد واحد على الأقل.', 'error');
            return false;
        }
    });
});
</script>
@endpush
@include('components.sweetalert')
