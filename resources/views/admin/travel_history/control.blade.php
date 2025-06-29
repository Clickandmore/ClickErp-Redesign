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

    {{-- $contracts, name=order_id --}}
    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('ingaz.the_order') }}</label>
        <div class="col-sm-5">
            <select name="order_id" id="order_id" class="form-control cu-form-design select2">
                <option value="">@lang('ingaz.select_order')</option>
                @forelse ($contracts as $contract)
                    <option value="{{$contract->id}}"
                        @if ((@$data && @$data->order_id == $contract->id) ||
                            (request()->get('id') == $contract->id) ||
                            (@$order && @$order->id == $contract->id) ||
                            (@$orderId == $contract->id))
                            selected
                        @endif>
                        @lang('ingaz.the_order'): {{$contract->id}} - @lang('ingaz.maid_id'): {{@$contract->user->name}}
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

    <div class="mb-3 row">
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
    </div>

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.travel_type') }}</label>
        <div class="col-sm-5">
            <select name="travel_type" id="travel_type" class="form-control cu-form-design select2">
                @forelse (__('travel_types.travel_types') ?? [] as $k => $name)
                <option value="{{$k}}" @if (@$data->travel_type == $k) selected @endif>
                    {{ $name }}
                </option>
                @empty
                @endforelse
            </select>
            <div>
                @error('travel_type')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.travel_with') }}</label>
        <div class="col-sm-5">
            <select name="travel_with" id="travel_with" class="form-control cu-form-design select2">
                @forelse (__('travel_types.travel_with') ?? [] as $k => $name)
                <option value="{{$k}}" @if (@$data->travel_with == $k) selected @endif>
                    {{ $name }}
                </option>
                @empty
                @endforelse
            </select>
            <div>
                @error('travel_with')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.status') }}</label>
        <div class="col-sm-5">
            <select name="status" id="status" class="form-control cu-form-design select2">
                @forelse (__('travel_types.status') ?? [] as $k => $name)
                <option value="{{$k}}" @if (@$data->status == $k) selected @endif>
                    {{ $name }}
                </option>
                @empty
                @endforelse
            </select>
            <div>
                @error('status')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-sm-4">
            @if(@$data)
                @if(@$data->status != 'completed')
                    <button type="button" class="btn btn-success mb-2" id="markCompletedBtn">
                        {{ __('admin.mark_trip_completed') }}
                    </button>
                @endif
                <button type="button" class="btn btn-primary" id="reviewTripBtn">
                    {{ __('admin.review_trip') }}
                </button>
            @endif
        </div>
    </div>

    <div class="mb-3 row">
        <label for="arrival_airport" class="col-sm-3 col-form-label">{{ __('admin.arrival_airport') }}</label>
        <div class="col-sm-5">
            <input type="text" name="arrival_airport" id="arrival_airport" value="{{ old('arrival_airport', @$data->arrival_airport) }}"
                class="form-control">
            <div>
                @error('arrival_airport')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="arrival_date" class="col-sm-3 col-form-label">{{ __('admin.arrival_date') }}</label>
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
        <label for="arrival_time" class="col-sm-3 col-form-label">{{ __('admin.arrival_time') }}</label>
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
                class="form-control">
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
                class="form-control">
            <div>
                @error('flight_number')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="mb-3 row">
        <label for="" class="col-sm-3 col-form-label">{{ __('admin.notes') }}</label>
        <div class="col-sm-5">
            <textarea name="notes" id="notes" cols="3" rows="3" class="form-control">{{ @$data->notes }}</textarea>
            <div>
                @error('notes')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

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
                    url: '/api/check-trip-exists',
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

    var userId = "{{ request()->userId }}";
    if( userId != "" || userId != null ){
        $('#user_id').val(userId).trigger('change');
    }

    var maidId = "{{ request()->maidId }}";
    if( maidId != "" || maidId != null ){
        $('#maid_id').val(maidId).trigger('change');
    }

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

});
</script>
@endpush
@include('components.sweetalert')
