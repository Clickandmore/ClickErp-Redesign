@extends('admin.layout.indexs.index')


@section('page-title')
{{ __('admin.travel') }}
@endsection


@section('css')
@endsection


@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL))

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

    {{-- $contracts, name=order_id --}}
    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('ingaz.contract') }}</label>
        <div class="col-sm-5">
            <select name="order_id" id="order_id" class="form-control cu-form-design select2">
                <option value="">@lang('ingaz.select_contract')</option>
                @forelse ($contracts as $contract)
                    <option value="{{$contract->id}}"
                        @if ((@$data && @$data->order_id == $contract->id) ||
                            (request()->get('id') == $contract->id) ||
                            (@$order && @$order->id == $contract->id) ||
                            (@$orderId == $contract->id))
                            selected
                        @endif>
                        @lang('ingaz.contract'): {{$contract->id}}
                        @if(@$contract->info->visa_number) (تأشيرة: {{@$contract->info->visa_number}}) @endif
                        ({{@$contract->status}})
                        @if(@$contract->info->contract_date) ({{@$contract->info->contract_date}}) @endif -
                        @lang('ingaz.client'): {{@$contract->user->name ?? 'غير محدد'}}
                        @if(@$contract->user->phone) ({{@$contract->user->phone}}) @endif -
                        @lang('ingaz.maid'): {{@$contract->maid->name ?? 'غير محدد'}}
                        @if(@$contract->maid->passport_num) (جواز: {{@$contract->maid->passport_num}}) @endif
                        @if(@$contract->maid->nationality) - {{@$contract->maid->nationality->sub_title}} @endif
                        @if(@$contract->maid->religion) - {{@$contract->maid->religion->title}} @endif
                        @if(@$contract->maid->office) - {{@$contract->maid->office->title}} @endif
                        @if(@$contract->maid->jobTitles && @$contract->maid->jobTitles->count() > 0) - {{@$contract->maid->jobTitles->first()->title}} @endif
                    </option>
                @empty
                @endforelse
            </select>
            <div>
                @error('order_id')
                <div class="text-danger">{{ $message }}</div>
                @enderror
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
                <option value="">اختر نوع الرحلة</option>
                <option value="international" @if (@$data->travel_type == 'international') selected @endif>رحلة دولية</option>
                <option value="domestic" @if (@$data->travel_type == 'domestic') selected @endif>رحلة داخلية</option>
            </select>
            <div>
                @error('travel_type')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- عرض مدينة تسليم العميل --}}
    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">مدينة تسليم العميل</label>
        <div class="col-sm-5">
            <input type="text" class="form-control" id="client_city" readonly placeholder="سيتم ملؤها تلقائياً عند اختيار العقد">
        </div>
    </div>

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">وسيلة السفر</label>
        <div class="col-sm-5">
            <select name="travel_with" id="travel_with" class="form-control cu-form-design select2">
                <option value="">اختر وسيلة السفر</option>
                <option value="airport" @if (@$data->travel_with == 'airport') selected @endif>مطار</option>
                <option value="group_transport" @if (@$data->travel_with == 'group_transport') selected @endif>نقل جماعي</option>
                <option value="train" @if (@$data->travel_with == 'train') selected @endif>قطار</option>
            </select>
            <div>
                @error('travel_with')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

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
        // Initialize select2 with specific options
        $('.select2').select2({
            allowClear: false,
            width: '100%'
        });

        // Function to set select2 value
        function setSelect2Value(selector, value) {
            if (value) {
                $(selector).val(value).trigger('change');
            }
        }

        // Set initial values after a short delay to ensure select2 is fully initialized
        setTimeout(function() {
            // Set order
            var orderId = "{{ @$orderId }}";
            if(orderId) {
                setSelect2Value('#order_id', orderId);
            }

            // Set user
            var userId = "{{ @$userId }}";
            if(userId) {
                setSelect2Value('#user_id', userId);
            }

            // Set maid
            var maidId = "{{ @$maidId }}";
            if(maidId) {
                setSelect2Value('#maid_id', maidId);
            }
        }, 100);

        // Prevent order selection from being cleared
        $('#order_id').on('select2:unselecting', function(e) {
            e.preventDefault();
        });

        // Additional event handler to ensure selection persists
        $('#order_id').on('change', function(e) {
            var currentValue = $(this).val();
            if (!currentValue && "{{ @$orderId }}") {
                $(this).val("{{ @$orderId }}").trigger('change');
            }
        });

        // Handle mark as completed button click
        $('#markCompletedBtn').click(function() {
            $('#status').val('completed').trigger('change');
        });

        // Handle review trip button click
        $('#reviewTripBtn').click(function() {
            // Add your review trip logic here
            swal({
                title: '{{ __("admin.review_trip") }}',
                text: '{{ __("admin.review_trip_confirmation") }}',
                icon: 'info',
                buttons: {
                    cancel: '{{ __("admin.cancel") }}',
                    confirm: '{{ __("admin.confirm") }}'
                }
            }).then((willReview) => {
                if (willReview) {
                    // Add your review trip API call here
                    // For example:
                    // $.ajax({
                    //     url: '/api/trip/review',
                    //     method: 'POST',
                    //     data: {
                    //         trip_id: "{{ @$data->id }}"
                    //     },
                    //     success: function(response) {
                    //         swal('{{ __("admin.success") }}', response.message, 'success');
                    //     }
                    // });
                }
            });
        });

        // Check if trip already exists for this order
        @if(!@$data)
        $('#order_id').on('change', function() {
            var orderId = $(this).val();
            if (orderId) {
                $.ajax({
                    url: '/api/admin/check-trip-exists',
                    method: 'GET',
                    data: {
                        order_id: orderId
                    },
                    success: function(response) {
                        if (response.exists) {
                            swal({
                                title: '{{ __("admin.error") }}',
                                text: '{{ __("admin.trip_already_exists") }}',
                                icon: 'error'
                            });
                            $('#order_id').val('').trigger('change');
                        }
                    }
                });
            }
        });
        @endif
    });
</script>
@endpush


@push('js')
<script>
$(document).ready(function () {

    // auto select order id if it is in the url
    var orderId = "{{ request()->id }}";
    if( orderId != "" || orderId != null ){
        $('#order_id').val(orderId).trigger('change');
    }

    // Auto-fill data when contract is selected
    $('#order_id').on('change', function() {
        var selectedContractId = $(this).val();

        if (selectedContractId) {
            var contracts = @json($contracts);
            var selectedContract = contracts.find(function(contract) {
                return contract.id == selectedContractId;
            });

            if (selectedContract) {
                // Auto-fill user_id
                if (selectedContract.user_id) {
                    $('#user_id').val(selectedContract.user_id);
                }

                // Auto-fill maid_id
                if (selectedContract.maid_id) {
                    $('#maid_id').val(selectedContract.maid_id);
                }

                // Auto-fill client city
                if (selectedContract.user && selectedContract.user.city) {
                    $('#client_city').val(selectedContract.user.city);
                } else {
                    $('#client_city').val('غير محدد');
                }
                console.log(selectedContract);
                console.log(selectedContract.user.city);
            }
        } else {
            // Clear fields if no contract selected
            $('#user_id').val('');
            $('#maid_id').val('');
            $('#client_city').val('');
        }
    });

    // Auto-fill data if contract_id is in URL
    var contractId = "{{ request()->contract_id }}";
    if (contractId) {
        $('#order_id').val(contractId).trigger('change');
    }

    // Auto-fill hidden fields if they exist in the data
    @if(@$data)
        @if(@$data->user_id)
            $('#user_id').val("{{ @$data->user_id }}");
        @endif
        @if(@$data->maid_id)
            $('#maid_id').val("{{ @$data->maid_id }}");
        @endif
    @endif

    // Auto-fill hidden fields if they exist in the request
    @if(request()->userId)
        $('#user_id').val("{{ request()->userId }}");
    @endif
    @if(request()->maidId)
        $('#maid_id').val("{{ request()->maidId }}");
    @endif

    // Auto-fill client city if we have order data
    @if(@$order && @$order->user && @$order->user->city)
        $('#client_city').val("{{ @$order->user->city }}");
    @endif

    // Auto-fill client city if we have contract data
    @if(@$orderId)
        var contracts = @json($contracts);
        var selectedContract = contracts.find(function(contract) {
            return contract.id == "{{ @$orderId }}";
        });
        if (selectedContract && selectedContract.user && selectedContract.user.city) {
            $('#client_city').val(selectedContract.user.city);
        }
    @endif

    // Auto-fill client city if we have contract data from URL
    @if(request()->contract_id)
        var contracts = @json($contracts);
        var selectedContract = contracts.find(function(contract) {
            return contract.id == "{{ request()->contract_id }}";
        });
        if (selectedContract && selectedContract.user && selectedContract.user.city) {
            $('#client_city').val(selectedContract.user.city);
        }
    @endif



    // Delete file
    $('.delete-file').click(function(event) {
        event.preventDefault();

        // Get the file ID from the data-id attribute
        var fileId = $(this).data('id');

        // Show confirmation dialog
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
                // Get the CSRF token
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                // Send delete request with Ajax
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
                                // Remove the row from the table
                                $('.delete-file[data-id="' + fileId + '"]').closest(
                                    'tr').remove();

                                // Check if no files remaining
                                if (response.no_files) {
                                    $('tbody').html(
                                        '<tr><td colspan="4">{{ __('admin.no_files_found_currently') }}</td></tr>'
                                    );
                                }
                            });
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.responseJSON.message;

                        // Check if the error message exists
                        if (errorMessage) {
                            swal('{{ __('admin.error') }}', errorMessage, 'error');
                        } else {
                            swal('{{ __('admin.error') }}',
                                '{{ __('admin.unknown_error') }}', 'error');
                        }
                    }
                });
            }
        });
    });

    // Form validation before submit
    $('form').on('submit', function(e) {
        var userId = $('#user_id').val();
        var maidId = $('#maid_id').val();
        var travelWith = $('#travel_with').val();

        if (!userId) {
            e.preventDefault();
            swal('{{ __('admin.error') }}', 'user id مطلوب.', 'error');
            return false;
        }

        if (!maidId) {
            e.preventDefault();
            swal('{{ __('admin.error') }}', 'maid id مطلوب.', 'error');
            return false;
        }

        if (!travelWith) {
            e.preventDefault();
            swal('{{ __('admin.error') }}', 'وسيلة السفر مطلوبة.', 'error');
            return false;
        }
    });
</script>
@endpush
@include('components.sweetalert')
