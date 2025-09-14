<!-- Bank Transfer Modal -->
<div class="modal fade" id="bankTransferModal" tabindex="-1" aria-labelledby="bankTransferModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bankTransferForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="bankTransferModalLabel">
                        {{ helperTrans('web.bank_transfer_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="order_id">

                    <div class="mb-3">
                        <div class="bankTransferNotes" style="display: none">{!! $setting['bank_transfer_data'] !!}</div>
                        <div class="sadadTransferNotes" style="display: none">{!! $setting['sadad_transfer_data'] !!}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="transfer_type" class="form-label">{{ __('admin.transfer_type') }}</label>
                        <select class="form-select" name="transfer_type" id="transfer_type" required>
                            <option value="bank_transfer">{{ __('admin.bank_transfer') }}</option>
                            <option value="sadad">{{ __('admin.sadad') }}</option>
                        </select>
                    </div>

                    @push('js')
                        <script>
                            $(document).ready(function() {
                                function toggleTransferNotes() {
                                    const transferType = $('#transfer_type').val();

                                    $('.bankTransferNotes, .sadadTransferNotes').hide();

                                    if (transferType === 'bank_transfer') {
                                        $('.bankTransferNotes').show();
                                    } else if (transferType === 'sadad') {
                                        $('.sadadTransferNotes').show();
                                    }
                                }

                                // Call the function on page load
                                toggleTransferNotes();

                                // Use 'on' for event delegation
                                $(document).on('change', '#transfer_type', function() {
                                    toggleTransferNotes();
                                });
                            });
                        </script>
                    @endpush

                    <div class="mb-3">
                        <label for="file" class="form-label">{{ helperTrans('web.upload_file') }}</label>
                        <input type="file" class="form-control" name="file" id="file" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">{{ helperTrans('web.notes') }}</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('web.close')
                        }}</button>
                    <button type="button" id="bankTransferSubmitBtn" class="btn btn-primary">{{
                        helperTrans('web.submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>



