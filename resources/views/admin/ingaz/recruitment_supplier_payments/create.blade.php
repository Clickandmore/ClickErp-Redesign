@extends('admin.layout.indexs.index')

@section('page-title', 'تسجيل دفعة وكيل خارجي')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-1">تسجيل دفعة وكيل خارجي</h4>
        <div class="text-muted mb-3">
            مدين حساب الوكيل / دائن البنك المسدد، على مركز تكلفة العقد.
        </div>

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><strong>العقد:</strong> #{{ $summary['contract_id'] }}</div>
                    <div class="col-md-3"><strong>العميل:</strong> {{ $summary['client'] }}</div>
                    <div class="col-md-3"><strong>العاملة:</strong> {{ $summary['worker'] }}</div>
                    <div class="col-md-3"><strong>الدولة:</strong> {{ $summary['nationality'] !== '' ? $summary['nationality'] : '-' }}</div>
                    <div class="col-md-3"><strong>المكتب الخارجي:</strong> {{ $summary['office'] }}</div>
                    <div class="col-md-3"><strong>فاتورة الوكيل:</strong> {{ number_format($summary['invoiced'], 2) }}</div>
                    <div class="col-md-3"><strong>المسدَّد:</strong> {{ number_format($summary['paid'], 2) }}</div>
                    <div class="col-md-3"><strong>المتبقي:</strong> {{ number_format($summary['remaining'], 2) }}</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('recruitment-supplier-payments.store', $summary['contract_id']) }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="date">تاريخ الدفعة</label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="{{ old('date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="value">المبلغ</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $summary['remaining'] }}"
                                   class="form-control" id="value" name="value"
                                   value="{{ old('value', $summary['remaining']) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="bank_account_id">البنك المسدد</label>
                            <select class="form-control select2" id="bank_account_id" name="bank_account_id" required>
                                <option value="">اختر الحساب</option>
                                @foreach ($bankAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((string) old('bank_account_id') === (string) $account->id)>
                                        {{ $account->code }} / {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="statement">بيان إضافي (اختياري)</label>
                            <input type="text" class="form-control" id="statement" name="statement"
                                   value="{{ old('statement') }}" maxlength="500">
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>تسجيل الدفعة</button>
                    <a class="btn btn-light" href="{{ route('recruitment-supplier-payments.index') }}">إلغاء</a>
                </div>
            </div>
        </form>
    </div>
@endsection
