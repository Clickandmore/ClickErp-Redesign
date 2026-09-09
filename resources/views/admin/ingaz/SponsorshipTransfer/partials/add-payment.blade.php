@if (session('payment_success'))
    <div class="alert alert-success">{{ session('payment_success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
@can(\App\PermKeys::SPONSOR_TRANSFER_ADD_PAYMENT)
    @if (!$transfer->isRentalWorkflow() && (float) $transfer->remaining_amount > 0)
        <details class="mt-3" @if ($errors->any()) open @endif>
            <summary class="btn btn-success">إضافة دفعة</summary>
            <form method="POST" action="{{ route('SponsorshipTransfer.addPayment', $transfer->id) }}"
                enctype="multipart/form-data" class="mt-3" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <input type="hidden" name="expected_paid_amount" value="{{ $transfer->paid_amount }}">
                <div class="mb-3">
                    <label for="transfer-payment-amount" class="form-label">مبلغ الدفعة</label>
                    <input id="transfer-payment-amount" class="form-control" type="number" name="amount" min="0.01"
                        max="{{ $transfer->remaining_amount }}" step="0.01" value="{{ old('amount') }}" required>
                </div>
                <div class="mb-3">
                    <label for="transfer-payment-date" class="form-label">تاريخ السداد</label>
                    <input id="transfer-payment-date" class="form-control" type="date" name="payment_date"
                        value="{{ old('payment_date', now()->toDateString()) }}" required>
                </div>
                <div class="mb-3">
                    <label for="transfer-payment-type" class="form-label">طريقة الدفع</label>
                    <select id="transfer-payment-type" class="form-select" name="payment_type" required>
                        @foreach (['cash' => 'نقدي', 'bank_transfer' => 'تحويل بنكي', 'mada' => 'مدى', 'tabby' => 'تابي', 'tamara' => 'تمارا'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="transfer-payment-account" class="form-label">البنك / حساب السداد</label>
                    <select id="transfer-payment-account" class="form-select" name="receipt_account_id" required>
                        <option value="">اختر حساب السداد</option>
                        @foreach ($receiptAccounts as $account)
                            <option value="{{ $account->id }}" @selected((string) old('receipt_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="transfer-payment-receipt" class="form-label">إيصال الدفع</label>
                    <input id="transfer-payment-receipt" type="file" class="form-control" name="payment_receipt"
                        accept=".jpg,.jpeg,.png,.pdf" required>
                    <small class="text-muted">PDF أو صورة، بحد أقصى 4 ميجابايت.</small>
                </div>
                <button type="submit" class="btn btn-success">تسجيل الدفعة</button>
            </form>
        </details>
    @endif
@endcan
