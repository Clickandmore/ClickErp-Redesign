@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Shelter contract")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_CONTROL))

    <form class="row g-3 contractForm" method="post" action="{{route("ShelterContract.store")}}">
        @csrf
        <div class="row p-3">



            <div class="col-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.contracts")}}</label>
                <select name="contract_id" id="contract_id" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    @foreach(($contracts ?? []) as $contract)
                    <option value="{{$contract->id}}"
                            data-passport="{{$contract->maid ? $contract->maid->passport_num : ''}}"
                            data-maid-name="{{$contract->maid ? $contract->maid->name : ''}}">
                        {{$contract->desc }} @if($contract->maid && $contract->maid->passport_num) - {{$contract->maid->passport_num}} @endif
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
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.salary_final_status")}}</label>
                <select name="salary_final_status" class="form-select form-control select2" data-validation="required">
                    <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                    <option value="received">تم استلام الراتب</option>
                    <option value="not_received">لم يتم استلام الراتب</option>
                </select>
            </div>

            <div class="col-4 mt-4">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.duration_from_enter")}}</label>
                <input type="text" name="duration_from_enter" class="form-control" readonly>
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
            let contractId = $(this).val();
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
                        } else {
                            console.log('No valid arrival date found in response');
                            console.log('Debug Info:', response.debug_info);
                            $('input[name="duration_from_enter"]').val('');
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
        });
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


@endsection