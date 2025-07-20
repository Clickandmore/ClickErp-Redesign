@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Shelter contract")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_CONTROL))

    <!-- زر إضافة العقد -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-plus-circle text-success me-2"></i>
                        {{helperTrans("ingaz.quick_create_contract")}}
                    </h5>
                    <p class="card-text">{{helperTrans("ingaz.create_completed_contract_description")}}</p>
                    <div class="d-flex gap-2">
                        {{-- <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createContractModal">
                            <i class="fas fa-plus me-1"></i>
                            {{helperTrans("ingaz.create_completed_contract")}}
                        </button> --}}
                        <a href="{{ route('Contract.createCompletedPage') }}" class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-1"></i>
                            {{helperTrans("ingaz.add_completed_contract")}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form class="row g-3 contractForm" method="post" action="{{route("ShelterContract.store")}}">
        @csrf
        <div class="row p-3">

            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.contracts")}}</label>
                <select name="contract_id" id="contract_id" class="form-select form-control select2" data-validation="required" style="flex: 1;">
                    <option disabled {{ !$selectedContractId ? 'selected' : '' }}>{{helperTrans('ingaz.choose')}}</option>
                    @foreach(($contracts ?? []) as $contract)
                    @php
                        $warrantyStatus = '';
                        if ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Completed->value) {
                            $warrantyStatus = ' (مكتمل)';
                        } elseif ($contract->status === \App\Enums\ingaz\ContractStatusEnum::UnderWarranty->value) {
                            $warrantyStatus = ' (تحت الضمان)';
                        } elseif ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Closed->value) {
                            $warrantyStatus = ' (مكتمل)';
                        }
                    @endphp
                    <option value="{{$contract->id}}"
                            data-passport="{{$contract->maid ? $contract->maid->passport_num : ''}}"
                            data-maid-name="{{$contract->maid ? $contract->maid->name : ''}}"
                            data-status="{{$contract->status}}"
                            {{ $selectedContractId == $contract->id ? 'selected' : '' }}>
                        {{$contract->desc }}{{ $warrantyStatus }} @if($contract->maid && $contract->maid->passport_num) - {{$contract->maid->passport_num}} @endif
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.shelter-places")}}</label>
                <select name="shelter_id" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @foreach(($shelters ?? []) as $shelter)
                    <option value="{{$shelter->id}}"> {{$shelter->title }} </option>
                    @endforeach
                </select>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.receipt_source")}}</label>
                <select name="receipt_source" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @forelse (__('shelter_worker_statuses.received_by') ?? [] as $k => $name)
                    <option value="{{$k}}">
                        {{ $name }}
                    </option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.maid date back")}}</label>
                <input type="date" name="maid_date_back" class="form-control" data-validation="required">
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.arrival_date")}}</label>
                <input type="date" name="arrival_date" id="arrival_date" class="form-control" data-validation="required">
                <small class="form-text text-muted">{{helperTrans("ingaz.arrival_date_help")}}</small>
            </div>



            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.duration_from_enter")}}</label>
                <input type="text" name="duration_from_enter" class="form-control" readonly>
            </div>
            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.salary_final_status")}}</label>
                <select name="salary_final_status" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    <option value="received">تم استلام الراتب</option>
                    <option value="not_received">لم يتم استلام الراتب</option>
                </select>
            </div>
            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.work_willingness")}}</label>
                <select name="wants_to_work" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    <option value="yes">{{helperTrans('ingaz.work_willingness_yes')}}</option>
                    <option value="no">{{helperTrans('ingaz.work_willingness_no')}}</option>
                    <option value="unknown">{{helperTrans('ingaz.work_willingness_unknown')}}</option>
                </select>
            </div>

            <div class="col-12 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.back_reasons")}}</label>
                <textarea name="back_reasons" rows="4" class="form-control" data-validation="required"></textarea>
            </div>

        </div>

        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__('buttons.save')}}</button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

<!-- Modal لإنشاء عقد جديد -->
<div class="modal fade" id="createContractModal" tabindex="-1" aria-labelledby="createContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createContractModalLabel">
                    <i class="fas fa-check-circle me-2"></i>{{helperTrans("ingaz.create_completed_contract")}}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createContractForm">
                    @csrf
                    <div class="row">
                        <!-- نوع العميل -->
                        <div class="col-12 mb-3">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>{{helperTrans("ingaz.client_type")}}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="client_type" id="existing_client" value="existing" checked>
                                        <label class="form-check-label" for="existing_client">
                                            <i class="fas fa-user-check me-1"></i>{{helperTrans("ingaz.existing_client")}}
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="client_type" id="new_client" value="new">
                                        <label class="form-check-label" for="new_client">
                                            <i class="fas fa-user-plus me-1"></i>{{helperTrans("ingaz.new_client")}}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- اختيار عميل موجود -->
                        <div id="existing_client_section" class="col-12 mb-3">
                            <label class="form-label">{{helperTrans("ingaz.select_existing_client")}} *</label>
                            <select name="existing_client_id" id="existing_client_id" class="form-select select2-ajax-client" style="width:100%"></select>
                        </div>

                        <!-- معلومات العميل الجديد -->
                        <div id="new_client_section" class="col-12 mb-3" style="display: none;">
                            <h6>{{helperTrans("ingaz.client_information")}}</h6>
                        </div>

                        <div class="col-6 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.client_name")}} *</label>
                            <input type="text" name="client_name" class="form-control">
                        </div>

                        <div class="col-6 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.client_phone")}} *</label>
                            <input type="tel" name="client_phone" class="form-control">
                        </div>

                        <div class="col-6 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.email")}}</label>
                            <input type="email" name="client_email" class="form-control">
                        </div>

                        <div class="col-6 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.nationality")}}</label>
                            <select name="client_nationality_id" class="form-select">
                                <option value="">{{helperTrans('ingaz.choose')}}</option>
                                @foreach(\App\Models\ingaz\Nationality::all() as $nationality)
                                    <option value="{{$nationality->id}}">{{$nationality->name_ar}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.national_num")}}</label>
                            <input type="text" name="client_national_num" id="client_national_num" class="form-control">
                        </div>

                        <div class="col-6 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.birthdate")}}</label>
                            <input type="date" name="client_birthdate" id="client_birthdate" class="form-control">
                        </div>

                        <div class="col-6 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.city")}}</label>
                            <input type="text" name="city" class="form-control">
                        </div>

                        <div class="col-12 new-client-field" style="display: none;">
                            <label class="form-label">{{helperTrans("ingaz.address")}}</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>

                        <!-- معلومات العاملة -->
                        <div class="col-12 mb-3 mt-3">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>{{helperTrans("ingaz.maid_information")}}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label">{{helperTrans("ingaz.maid")}} *</label>
                                            <select name="maid_id" id="maid_id" class="form-select select2-ajax-maid" required style="width:100%"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- البحث عن العاملة -->
                        <div class="col-12 mb-3">
                            <label class="form-label">{{helperTrans("ingaz.search_maid")}}</label>
                            <div class="input-group">
                                <input type="text" id="maid_search" class="form-control" placeholder="{{helperTrans('ingaz.enter_passport_or_name')}}">
                                <button type="button" class="btn btn-outline-secondary" id="search_maid_btn">
                                    <i class="fa fa-search"></i> {{helperTrans("ingaz.search")}}
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="clear_maid_search">
                                    <i class="fa fa-times"></i> {{helperTrans("ingaz.clear")}}
                                </button>
                            </div>
                        </div>

                        <!-- تفاصيل العاملة المختارة -->
                        <div id="maid_details" class="col-12 mb-3" style="display: none;">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{helperTrans("ingaz.selected_maid_details")}}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <strong class="text-primary">{{helperTrans("ingaz.name")}}:</strong>
                                            <span id="maid_name_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <strong class="text-primary">{{helperTrans("ingaz.passport_num")}}:</strong>
                                            <span id="maid_passport_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <strong class="text-primary">{{helperTrans("ingaz.nationality")}}:</strong>
                                            <span id="maid_nationality_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <strong class="text-primary">{{helperTrans("ingaz.age")}}:</strong>
                                            <span id="maid_age_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <strong class="text-primary">{{helperTrans("ingaz.salary")}}:</strong>
                                            <span id="maid_salary_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <strong class="text-primary">{{helperTrans("ingaz.experience_years")}}:</strong>
                                            <span id="maid_experience_display" class="ms-2"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات العقد -->
                        <div class="col-12 mb-3 mt-3">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-file-contract me-2"></i>{{helperTrans("ingaz.contract_information")}}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label">{{helperTrans("ingaz.contract_date")}} *</label>
                                            <input type="date" name="contract_date" class="form-control" required>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label">{{helperTrans("ingaz.contract_duration")}} *</label>
                                            <input type="number" name="contract_duration" class="form-control" placeholder="{{helperTrans('ingaz.in_months')}}" required>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label">{{helperTrans("ingaz.visa_number")}} *</label>
                                            <input type="text" name="visa_number" class="form-control" required>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label">{{helperTrans("ingaz.msaned_number")}} *</label>
                                            <input type="text" name="msaned_number" class="form-control" required>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label">{{helperTrans("ingaz.tafwed_number")}}</label>
                                            <input type="text" name="tafwed_number" class="form-control">
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label">{{helperTrans("ingaz.visa_milad_date")}}</label>
                                            <input type="date" name="visa_milad_date" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>{{helperTrans("ingaz.cancel")}}
                </button>
                <button type="button" class="btn btn-success" id="createContractBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <i class="fas fa-check-circle me-1"></i>{{helperTrans("ingaz.create_completed_contract")}}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('js')
<script>
    $('.select2').select2({
            placeholder: 'Search for an order...',
            allowClear: true
        });
</script>

<script>
    var indexRoute = "{{ route('ShelterContract.index') }}";
</script>

<script>
    // Passport search functionality
    $(document).ready(function() {
        // Search button click handler
        $('#search_passport_btn').on('click', function() {
            searchContractsByPassport();
        });

        // Enter key press handler for search input
        $('#passport_search').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                searchContractsByPassport();
            }
        });

        // Clear search button click handler
        $('#clear_search_btn').on('click', function() {
            $('#passport_search').val('');
            loadAllContracts();
        });

        // Function to search contracts by passport
        function searchContractsByPassport() {
            let passportNumber = $('#passport_search').val().trim();

            if (!passportNumber) {
                loadAllContracts();
                return;
            }

            $.ajax({
                url: "{{ route('search.contracts.by.passport') }}",
                type: 'GET',
                data: { passport_number: passportNumber },
                beforeSend: function() {
                    $('#search_passport_btn').html('<i class="fa fa-spinner fa-spin"></i> {{helperTrans("ingaz.searching")}}');
                },
                success: function(response) {
                    updateContractDropdown(response.contracts);
                    $('#search_passport_btn').html('<i class="fa fa-search"></i> {{helperTrans("ingaz.search")}}');
                },
                error: function(xhr, status, error) {
                    console.error('Error searching contracts:', error);
                    $('#search_passport_btn').html('<i class="fa fa-search"></i> {{helperTrans("ingaz.search")}}');
                    // Show error message
                    alert('{{helperTrans("ingaz.error_searching_contracts")}}');
                }
            });
        }

        // Function to update contract dropdown with search results
        function updateContractDropdown(contracts) {
            let $select = $('#contract_id');
            $select.empty();
            $select.append('<option disabled selected>{{helperTrans("ingaz.choose")}}</option>');

            if (contracts.length === 0) {
                $select.append('<option disabled>{{helperTrans("ingaz.no_contracts_found")}}</option>');
            } else {
                contracts.forEach(function(contract) {
                    let optionText = contract.desc;
                    if (contract.passport_number) {
                        optionText += ' - ' + contract.passport_number;
                    }

                    $select.append(
                        '<option value="' + contract.id + '" ' +
                        'data-passport="' + (contract.passport_number || '') + '" ' +
                        'data-maid-name="' + (contract.maid_name || '') + '">' +
                        optionText +
                        '</option>'
                    );
                });
            }

            // Reinitialize Select2
            $select.select2('destroy').select2({
                placeholder: '{{helperTrans("ingaz.choose")}}',
                allowClear: true
            });
        }

        // Function to load all contracts (clear search)
        function loadAllContracts() {
            $.ajax({
                url: "{{ route('ShelterContract.create') }}",
                type: 'GET',
                success: function(response) {
                    // Extract the contracts from the response
                    // This is a simplified approach - in a real implementation,
                    // you might want to create a separate endpoint for getting all contracts
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Error loading contracts:', error);
                    location.reload();
                }
            });
        }
    });
</script>

<script>
    // Calculate duration from enter automatically
    $(document).ready(function() {
        $('select[name="contract_id"]').on('change', function() {
            calculateDuration();
        });

        // إذا كان هناك عقد محدد عند تحميل الصفحة، احسب المدة
        @if($selectedContractId)
            // تأكد من اختيار العقد المحدد في القائمة
            setTimeout(function() {
                // إعادة تهيئة Select2 مع القيمة المحددة
                $('#contract_id').select2('destroy').select2({
                    placeholder: '{{helperTrans("ingaz.choose")}}',
                    allowClear: true
                });

                // اختيار العقد المحدد
                $('#contract_id').val('{{ $selectedContractId }}').trigger('change');

                // احسب المدة بعد اختيار العقد
                setTimeout(function() {
                    calculateDuration();
                }, 500);
            }, 300);
        @endif

        // إضافة العقد الجديد إلى القائمة إذا لم يكن موجوداً
        @if($selectedContractId)
            setTimeout(function() {
                let contractExists = false;
                $('#contract_id option').each(function() {
                    if ($(this).val() == '{{ $selectedContractId }}') {
                        contractExists = true;
                        return false;
                    }
                });

                if (!contractExists) {
                    // إضافة العقد الجديد إلى القائمة
                    let newOption = new Option('عقد جديد - {{ $selectedContractId }}', '{{ $selectedContractId }}', true, true);
                    $('#contract_id').append(newOption).trigger('change');
                }
            }, 1000);
        @endif

        // حساب المدة عند تغيير تاريخ الوصول يدوياً
        $('#arrival_date').on('change', function() {
            calculateDurationFromArrivalDate();
        });

        // عرض حالة الضمان عند اختيار العقد
        $('#contract_id').on('change', function() {
            let selectedOption = $(this).find('option:selected');
            let contractStatus = selectedOption.data('status');
            let warrantyInfo = '';

            if (contractStatus === 'completed') {
                warrantyInfo = '<div class="alert alert-success mt-2"><i class="fas fa-check-circle me-2"></i>هذا العقد مكتمل (جاهز للإيواء)</div>';
            } else if (contractStatus === 'under_warranty') {
                warrantyInfo = '<div class="alert alert-info mt-2"><i class="fas fa-shield-alt me-2"></i>هذا العقد تحت الضمان (وصلت المملكة حديثاً)</div>';
            } else if (contractStatus === 'closed') {
                warrantyInfo = '<div class="alert alert-success mt-2"><i class="fas fa-check-circle me-2"></i>هذا العقد مكتمل (مر على الوصول 3 أشهر)</div>';
            }

            // إزالة المعلومات السابقة
            $('.warranty-info').remove();

            // إضافة المعلومات الجديدة
            if (warrantyInfo) {
                $('#contract_id').after(warrantyInfo);
            }
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

        function calculateDuration() {
            let contractId = $('select[name="contract_id"]').val();
            console.log('Calculating duration for contract ID:', contractId);

            if (contractId) {
                $.ajax({
                    url: "{{ route('get.contract.details') }}",
                    type: 'GET',
                    data: { contract_id: contractId },
                    success: function(response) {
                        console.log('Contract Details Response:', response);

                        // Get the arrival date from the response
                        let arrivalDate = response.arrival_date;
                        console.log('Arrival Date from Response:', arrivalDate);
                        console.log('Contract Status:', response.contract_status);
                        console.log('Has Info:', response.has_info);
                        console.log('Info Arrival Date:', response.info_arrival_date);
                        console.log('Maid Arrival Date:', response.maid_arrival_date);
                        console.log('Final Arrival Date:', response.final_arrival_date);

                        if (arrivalDate) {
                            // Parse the date string to a Date object
                            let arrivalDateObj = new Date(arrivalDate);
                            let today = new Date();

                            console.log('Parsed Arrival Date:', arrivalDateObj);
                            console.log('Today:', today);

                            // Calculate the difference in days
                            let diffTime = Math.abs(today - arrivalDateObj);
                            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            console.log('Difference in days:', diffDays);

                            // Format the duration text
                            let durationText = diffDays + ' يوم';
                            console.log('Duration Text:', durationText);

                            // Update the input field
                            $('input[name="duration_from_enter"]').val(durationText);

                            // Fill arrival date field
                            $('#arrival_date').val(arrivalDate);
                        } else {
                            console.log('No valid arrival date found in response');
                            console.log('Debug Info:', response.debug_info);
                            console.log('Trying to get arrival date from maid...');

                            // Try to get arrival date from maid if available
                            if (response.maid_arrival_date && response.maid_arrival_date !== 'null') {
                                let maidArrivalDate = new Date(response.maid_arrival_date);
                                let today = new Date();
                                let diffTime = Math.abs(today - maidArrivalDate);
                                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                let durationText = diffDays + ' يوم';
                                console.log('Using maid arrival date:', response.maid_arrival_date);
                                console.log('Calculated duration:', durationText);
                                $('input[name="duration_from_enter"]').val(durationText);
                                $('#arrival_date').val(response.maid_arrival_date);
                            } else {
                                $('input[name="duration_from_enter"]').val('');
                                $('#arrival_date').val('');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching contract details:', error);
                        console.log('Response:', xhr.responseText);
                        $('input[name="duration_from_enter"]').val('');
                    }
                });
            } else {
                $('input[name="duration_from_enter"]').val('');
            }
        }
    });
</script>

<script>
    $(document).on('submit', '.contractForm', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            let url = $(this).attr('action');
            let submitButton = $(e.originalEvent.submitter);
            let buttonText = submitButton.text();
            let modalResult = $("#createModal");
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
                    console.log(200)
                    window.location.href = indexRoute;
                    submitButton.html(buttonText)
                    if (modalResult.is(":visible")) {
                        modalResult.modal('toggle');
                    }
                    console.log(modalResult.is(':visible'));
                },
                error: function (errorObj, errorText, errorThrown) {
                    console.log(500)
                    console.log(errorObj)
                    handleErrorResponce(errorObj);
                }
            });
        });
</script>

<script>
    // إنشاء عقد جديد
    $(document).ready(function() {
        // تهيئة Select2 العادية للحقول الأخرى
        $('.select2').select2({
            placeholder: '{{helperTrans("ingaz.choose")}}',
            allowClear: true
        });

        // Select2 AJAX للعملاء
        $('#existing_client_id').select2({
            placeholder: '{{helperTrans("ingaz.choose")}}',
            allowClear: true,
            ajax: {
                url: '{{ route("Contract.searchClients") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term || ''
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            },
            minimumInputLength: 1,
            language: {
                noResults: function() {
                    return "{{helperTrans('ingaz.no_results_found')}}";
                },
                searching: function() {
                    return "{{helperTrans('ingaz.searching')}}";
                }
            }
        });

        // Select2 AJAX للعاملة
        $('#maid_id').select2({
            placeholder: '{{helperTrans("ingaz.choose")}}',
            allowClear: true,
            ajax: {
                url: '{{ route("Contract.searchMaids") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term || ''
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.maids.map(function(maid) {
                            return {
                                id: maid.id,
                                text: maid.name + ' - ' + maid.passport_num + (maid.nationality ? ' - ' + maid.nationality : ''),
                                maid: maid
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 1,
            language: {
                noResults: function() {
                    return "{{helperTrans('ingaz.no_maids_found')}}";
                },
                searching: function() {
                    return "{{helperTrans('ingaz.searching')}}";
                }
            }
        });

        // عند اختيار العاملة، عرض التفاصيل
        $('#maid_id').on('select2:select', function(e) {
            var maid = e.params.data.maid;
            if (maid) {
                $('#maid_name_display').text(maid.name);
                $('#maid_passport_display').text(maid.passport_num);
                $('#maid_nationality_display').text(maid.nationality || 'غير محدد');
                $('#maid_age_display').text(maid.age || 'غير محدد');
                $('#maid_salary_display').text(maid.salary || 'غير محدد');
                $('#maid_experience_display').text(maid.experience_years || 'غير محدد');
                $('#maid_details').show();
            }
        });

        $('#maid_id').on('select2:clear', function(e) {
            $('#maid_details').hide();
        });

        // استيراد بيانات العميل عند اختياره
        $('#existing_client_id').on('select2:select', function(e) {
            var client = e.params.data;
            if (client) {
                // استيراد رقم الهوية
                if (client.national_num) {
                    $('#client_national_num').val(client.national_num);
                }

                // استيراد تاريخ الميلاد
                if (client.birthdate) {
                    $('#client_birthdate').val(client.birthdate);
                }
            }
        });

        // إعادة تهيئة Select2 عند فتح المودال
        $('#createContractModal').on('shown.bs.modal', function() {
            $('#existing_client_id').select2('destroy').select2({
                placeholder: '{{helperTrans("ingaz.choose")}}',
                allowClear: true,
                ajax: {
                    url: '{{ route("Contract.searchClients") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });

            // استيراد بيانات العميل عند اختياره في المودال
            $('#existing_client_id').off('select2:select').on('select2:select', function(e) {
                var client = e.params.data;
                if (client) {
                    // استيراد رقم الهوية
                    if (client.national_num) {
                        $('#client_national_num').val(client.national_num);
                    }

                    // استيراد تاريخ الميلاد
                    if (client.birthdate) {
                        $('#client_birthdate').val(client.birthdate);
                    }
                }
            });

            $('#maid_id').select2('destroy').select2({
                placeholder: '{{helperTrans("ingaz.choose")}}',
                allowClear: true,
                ajax: {
                    url: '{{ route("Contract.searchMaids") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.maids.map(function(maid) {
                                return {
                                    id: maid.id,
                                    text: maid.name + ' - ' + maid.passport_num + (maid.nationality ? ' - ' + maid.nationality : ''),
                                    maid: maid
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        });

        // تبديل نوع العميل
        $('input[name="client_type"]').on('change', function() {
            let clientType = $(this).val();

            if (clientType === 'existing') {
                $('#existing_client_section').show();
                $('.new-client-field').hide();
                $('#new_client_section').hide();

                // إزالة required من حقول العميل الجديد
                $('.new-client-field input, .new-client-field select').prop('required', false);
                $('#existing_client_id').prop('required', true);
            } else {
                $('#existing_client_section').hide();
                $('.new-client-field').show();
                $('#new_client_section').show();

                // إضافة required لحقول العميل الجديد
                $('.new-client-field input[name="client_name"], .new-client-field input[name="client_phone"]').prop('required', true);
                $('#existing_client_id').prop('required', false);
            }
        });

        // البحث عن العاملة
        $('#search_maid_btn').on('click', function() {
            searchMaids();
        });

        $('#maid_search').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                searchMaids();
            }
        });

        $('#clear_maid_search').on('click', function() {
            $('#maid_search').val('');
            loadAllMaids();
        });

        // دالة البحث عن العمالة
        function searchMaids() {
            let searchTerm = $('#maid_search').val().trim();

            if (!searchTerm) {
                loadAllMaids();
                return;
            }

            $.ajax({
                url: "{{ route('Contract.searchMaids') }}",
                type: 'GET',
                data: { search: searchTerm },
                beforeSend: function() {
                    $('#search_maid_btn').html('<i class="fa fa-spinner fa-spin"></i> {{helperTrans("ingaz.searching")}}');
                },
                success: function(response) {
                    updateMaidDropdown(response.maids);
                    $('#search_maid_btn').html('<i class="fa fa-search"></i> {{helperTrans("ingaz.search")}}');
                },
                error: function(xhr, status, error) {
                    console.error('Error searching maids:', error);
                    $('#search_maid_btn').html('<i class="fa fa-search"></i> {{helperTrans("ingaz.search")}}');
                    Swal.fire({
                        icon: 'error',
                        title: '{{helperTrans("ingaz.error")}}',
                        text: '{{helperTrans("ingaz.error_searching_maids")}}',
                        confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                    });
                }
            });
        }

        // دالة تحديث قائمة العمالة
        function updateMaidDropdown(maids) {
            let $select = $('#maid_id');
            $select.empty();
            $select.append('<option value="">{{helperTrans("ingaz.choose")}}</option>');

            if (maids.length === 0) {
                $select.append('<option disabled>{{helperTrans("ingaz.no_maids_found")}}</option>');
            } else {
                maids.forEach(function(maid) {
                    let optionText = maid.name + ' - ' + maid.passport_num;
                    if (maid.nationality) {
                        optionText += ' - ' + maid.nationality;
                    }

                    $select.append(
                        '<option value="' + maid.id + '" ' +
                        'data-name="' + maid.name + '" ' +
                        'data-passport="' + maid.passport_num + '" ' +
                        'data-nationality="' + (maid.nationality || '') + '" ' +
                        'data-age="' + maid.age + '" ' +
                        'data-salary="' + maid.salary + '" ' +
                        'data-experience="' + maid.experience_years + '">' +
                        optionText +
                        '</option>'
                    );
                });
            }

            // إعادة تهيئة Select2
            $select.select2('destroy').select2({
                placeholder: '{{helperTrans("ingaz.choose")}}',
                allowClear: true
            });
        }

        // دالة تحميل جميع العمالة
        function loadAllMaids() {
            $.ajax({
                url: "{{ route('Contract.getAllMaids') }}",
                type: 'GET',
                success: function(response) {
                    updateMaidDropdown(response.maids);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading maids:', error);
                    location.reload();
                }
            });
        }

        $('#createContractBtn').on('click', function() {
            let form = $('#createContractForm');
            let formData = new FormData(form[0]);
            let button = $(this);
            let originalText = button.text();
            let spinner = button.find('.spinner-border');

            // التحقق من صحة النموذج
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            // التحقق من نوع العميل
            let clientType = $('input[name="client_type"]:checked').val();
            if (clientType === 'existing' && !$('#existing_client_id').val()) {
                Swal.fire({
                    icon: 'error',
                    title: '{{helperTrans("ingaz.error")}}',
                    text: '{{helperTrans("ingaz.please_select_client")}}',
                    confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                });
                return;
            }

            // إظهار spinner
            button.prop('disabled', true);
            spinner.removeClass('d-none');

            $.ajax({
                url: "{{ route('Contract.createCompleted') }}",
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // إضافة العقد الجديد إلى القائمة
                        let newOption = new Option(
                            response.contract.desc + ' - ' + response.contract.maid.passport_num,
                            response.contract.id,
                            true,
                            true
                        );
                        $('#contract_id').append(newOption).trigger('change');

                        // إغلاق modal
                        $('#createContractModal').modal('hide');

                        // إظهار رسالة نجاح
                        Swal.fire({
                            icon: 'success',
                            title: '{{helperTrans("ingaz.success")}}',
                            text: response.message,
                            confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                        });

                        // إعادة تعيين النموذج
                        form[0].reset();
                        $('#maid_details').hide();
                        $('.new-client-field').hide();
                        $('#existing_client_section').show();
                        $('#new_client_section').hide();
                        $('input[name="client_type"][value="existing"]').prop('checked', true);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{helperTrans("ingaz.error")}}',
                            text: response.message,
                            confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{helperTrans("ingaz.error_creating_contract")}}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: '{{helperTrans("ingaz.error")}}',
                        text: errorMessage,
                        confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                    });
                },
                complete: function() {
                    // إخفاء spinner
                    button.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // إعادة تعيين النموذج عند إغلاق modal
        $('#createContractModal').on('hidden.bs.modal', function() {
            $('#createContractForm')[0].reset();
            $('#maid_details').hide();
            $('.new-client-field').hide();
            $('#existing_client_section').show();
            $('#new_client_section').hide();
            $('input[name="client_type"][value="existing"]').prop('checked', true);
            $('#maid_search').val('');
            loadAllMaids();
        });
    });
</script>

@endsection
