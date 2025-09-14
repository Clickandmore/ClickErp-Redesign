<!-- Travel History Modal -->
<div class="modal fade" id="travelHistoryModal" tabindex="-1" aria-labelledby="travelHistoryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="travelHistoryModalLabel">{{ __('admin.travelHistoryModal') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="travelHistoryForm" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3 row">
                        <label for="travel_type" class="col-sm-3 col-form-label">{{ __('admin.travel_type') }}</label>
                        <div class="col-sm-5">
                            <select name="travel_type" id="travel_type" class="form-control cu-form-design select2">
                                @forelse (__('travel_types.travel_types') ?? [] as $k => $name)
                                    <option value="{{$k}}">{{ $name }}</option>
                                @empty
                                    <option value="">{{ __('No travel types available') }}</option>
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
                        <label for="travel_with" class="col-sm-3 col-form-label">{{ __('admin.travel_with') }}</label>
                        <div class="col-sm-5">
                            <select name="travel_with" id="travel_with" class="form-control cu-form-design select2">
                                @forelse (__('travel_types.travel_with') ?? [] as $k => $name)
                                    <option value="{{$k}}">{{ $name }}</option>
                                @empty
                                    <option value="">{{ __('No travel companions available') }}</option>
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
                        <label for="arrival_airport"
                            class="col-sm-3 col-form-label">{{ __('admin.arrival_airport') }}</label>
                        <div class="col-sm-5">
                            <input type="text" name="arrival_airport" id="arrival_airport"
                                value="{{ old('arrival_airport') }}" class="form-control" required>
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
                            <input type="date" name="arrival_date" id="arrival_date" value="{{ old('arrival_date') }}"
                                class="form-control" required>
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
                            <input type="time" name="arrival_time" id="arrival_time" value="{{ old('arrival_time') }}"
                                class="form-control" required>
                            <div>
                                @error('arrival_time')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="flight_company"
                            class="col-sm-3 col-form-label">{{ __('admin.flight_company') }}</label>
                        <div class="col-sm-5">
                            <input type="text" name="flight_company" id="flight_company"
                                value="{{ old('flight_company') }}" class="form-control" required>
                            <div>
                                @error('flight_company')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="flight_number"
                            class="col-sm-3 col-form-label">{{ __('admin.flight_number') }}</label>
                        <div class="col-sm-5">
                            <input type="text" name="flight_number" id="flight_number"
                                value="{{ old('flight_number') }}" class="form-control" required>
                            <div>
                                @error('flight_number')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label for="notes" class="form-label">{{ __('admin.notes') }}</label>
                        <textarea class="form-control" id="notes" name="notes"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="files" class="form-label">{{ __('admin.files') }}</label>
                        <input type="file" class="form-control" id="files" name="files[]" multiple>
                    </div>
                    <input type="hidden" id="order_id" name="order_id">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('js')
    {{--
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    <script type="text/javascript">
        $(document).ready(function () {
            // Set order_id when the modal is opened
            $('#travelHistoryModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var orderId = button.data('order-id'); // Extract info from data-* attributes
                var modal = $(this);
                modal.find('#order_id').val(orderId); // Set order_id in the form
            });

            // Handle form submission
            $('#travelHistoryForm').off('submit').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission

                var formData = new FormData(this); // Create FormData object
                $.ajax({
                    url: '{{ route("storeTravelHistory") }}', // Route to handle the form submission
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        // Handle success response
                        $('#travelHistoryModal').modal('hide'); // Hide the modal
                        location.reload(); // Reload the page
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error adding travel history. Please try again.');
                    }
                });
            });
        });
    </script>
@endpush