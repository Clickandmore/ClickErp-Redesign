<!-- *** pay model *** -->
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" class="submit-global newPayFormClass">
                    @csrf
                    <input id="details_list_address_id" type="hidden" value="" name="details_list[address_id]">
                    <input id="details_list_package_id" type="hidden" value="" name="details_list[package_id]">
                    <input id="details_list_admin_id" type="hidden" value="" name="details_list[admin_id]">
                    <input id="details_list_deliver_maid" type="hidden" value="0" name="details_list[deliver_maid]">
                    <input id="maid_id" type="hidden" value="{{ $maid->id }}" name="maid_id">
                    <input type="hidden" value="online" name="payment_method">
                    <input type="hidden" name="job_type_id" value="{{ $type->id }}">
                    <div class="payModal">
                        <ul>
                            <li>
                                <label for="bank_transfer">
                                    <div class="paymethod">
                                        <h6>{{ helperTrans('web.bank_transfer') }}</h6>
                                        <img height="45" src="{{ asset('web') }}/assets/images/transfer.png" alt="">
                                    </div>
                                    <input type="radio" name="payment_type" id="bank_transfer" value="bank_transfer">
                                </label>
                            </li>
                            <li>
                                <label for="pay_later">
                                    <div class="paymethod">
                                        <h6>{{ __('admin.pay_later') }}</h6>
                                        <img height="45" src="{{ asset('web') }}/assets/images/pay_later.png" alt="">
                                    </div>
                                    <input type="radio" name="payment_type" id="pay_later" value="pay_later">
                                </label>
                            </li>

                            {{-- <li>
                                <label for="mada">
                                    <div class="paymethod">
                                        <h6>{{ helperTrans('web.duration') }}</h6>
                                        <img src="{{ asset('web') }}/assets/images/mada.png" alt="">
                                    </div>
                                    <input checked type="radio" name="payment_type" id="mada" value="mada">
                                </label>
                            </li>
                            <li>
                                <label for="visa">
                                    <div class="paymethod">
                                        <h6>{{ helperTrans('web.visa') }}</h6>
                                        <img src="{{ asset('web') }}/assets/images/visa.png" alt="">
                                    </div>
                                    <input type="radio" name="payment_type" id="visa" value="visa">
                                </label>
                            </li>
                            <li>
                                <label for="masterCard">
                                    <div class="paymethod">
                                        <h6>{{ helperTrans('web.mastercard') }}</h6>
                                        <img src="{{ asset('web') }}/assets/images/master-card.png" alt="">
                                    </div>
                                    <input type="radio" name="payment_type" id="masterCard" value="masterCard">
                                </label>
                            </li> --}}
                            <button type="submit">
                                {{ helperTrans('web.payment') }}
                            </button>
                        </ul>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
