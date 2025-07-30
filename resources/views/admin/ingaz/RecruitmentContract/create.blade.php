@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Recruitment contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content')

@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))

    <!-- التبويبات أعلى الصفحة -->
    <ul class="nav nav-tabs mb-4" id="contractTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-order" data-bs-toggle="tab" data-bs-target="#tab-order-pane" type="button" role="tab" aria-controls="tab-order-pane" aria-selected="true">
            <span><i class="fas fa-list-check me-1"></i>طلب مسبق</span>

        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-new" data-bs-toggle="tab" data-bs-target="#tab-new-pane" type="button" role="tab" aria-controls="tab-new-pane" aria-selected="false">
           <span><i class="fas fa-user-plus me-1"></i>عقد جديد</span>
        </button>
      </li>
    </ul>


    <div class="tab-content" id="contractTabsContent">
      <!-- تبويب الطلب المسبق (الحالي) -->
      <div class="tab-pane fade show active" id="tab-order-pane" role="tabpanel" aria-labelledby="tab-order">
    <form id="maidForm" class="row g-3 contractForm" method="post" action="{{route("RecruitmentContract.store")}}">
        @csrf
        <input type="hidden" name="client_type" value="ex_client" id="client_type">
        <input type="hidden" name="maid_id" id="maid_id">

        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">معلومات العميل</h4>
                </div>
                <div class="card-body">
                        {{-- <div class="col-12 mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="ex_client" value="ex_client" checked>
                            <label class="form-check-label" for="ex_client">{{helperTrans("ingaz.ex_client")}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="new_client" value="new_client">
                            <label class="form-check-label" for="new_client">{{helperTrans("ingaz.new_client")}}</label>
                        </div>
                        </div> --}}

                    <div id="order-selection" class="col-12 mb-4">
                        <label class="form-label">{{helperTrans("ingaz.select_order")}}</label>
                        <select name="order_id" id="order_id" class="form-select form-control select2" data-validation="required">
                            <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                            @foreach($orders as $order)
                                <option value="{{$order->id}}"
                                    data-client-name="{{$order->user->name ?? ''}}"
                                    data-client-phone="{{$order->user->phone ?? ''}}"
                                    data-maid-name="{{$order->maid->name ?? ''}}"
                                    data-maid-passport="{{$order->maid->passport_num ?? ''}}"
                                    data-client-nationality="{{$order->user->nationality_id ?? ''}}"
                                    data-maid-id="{{$order->maid->id ?? ''}}"
                                    data-maid-nationality="{{$order->maid->nationality_id ?? ''}}"
                                    data-maid-religion="{{$order->maid->religion_id ?? ''}}"
                                    data-maid-job="{{$order->maid->job_types && $order->maid->job_types->first() && $order->maid->job_types->first()->translations && $order->maid->job_types->first()->translations->first() ? $order->maid->job_types->first()->translations->first()->title : ''}}"
                                    data-maid-salary="{{$order->maid->salary ?? ''}}"
                                    data-maid-experience="{{$order->maid->experience_years ?? ''}}"
                                    data-maid-age="{{$order->maid->age ?? ''}}"
                                    @if(isset($orderId) && $orderId == $order->id) selected @endif>
                                    {{$order->user->name ?? ''}} - {{$order->user->phone ?? ''}} - {{$order->maid->name ?? ''}} ({{$order->maid->passport_num ?? ''}}) - {{$order->jobType->title ?? ''}}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @include('admin.ingaz.RecruitmentContract.client-section')
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">معلومات الخادمة</h4>
                </div>
                <div class="card-body">
                    @include('admin.ingaz.RecruitmentContract.maid-section')
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">معلومات العقد</h4>
                </div>
                <div class="card-body">
                    @include('admin.ingaz.RecruitmentContract.contract-section')
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button class="btn btn-primary btn-lg px-5" type="submit">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        {{__('buttons.save')}}
                    </button>
                </div>
            </div>
        </div>
    </form>
      </div>
      <!-- تبويب العقد الجديد بدون طلب مسبق -->
      <div class="tab-pane fade" id="tab-new-pane" role="tabpanel" aria-labelledby="tab-new">
        <form id="newContractForm" method="post" action="{{ route('RecruitmentContract.store') }}">
          @csrf
          <input type="hidden" name="contract_type" value="new_contract">
          <input type="hidden" name="client_type" value="new_client">

          <div class="card mb-4">
            <div class="card-header bg-info text-white">
              <h4 class="mb-0"><i class="fas fa-users me-2"></i>عقد جديد بدون طلب مسبق</h4>
            </div>
            <div class="card-body">
              <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                  <label for="new_existing_client_id" class="form-label">اختر عميل <span class="text-danger">*</span></label>
                  <select name="new_existing_client_id" id="new_existing_client_id" class="form-select select2" style="width:100%">
                    <option value="" selected disabled>اختر عميل...</option>
                    @if(isset($existing_clients))
                      @foreach($existing_clients as $client)
                        <option value="{{$client->id}}"
                          data-name="{{$client->name}}"
                          data-phone="{{$client->phone}}"
                          data-email="{{$client->email}}"
                          data-nationality="{{$client->nationality_id}}"
                          data-national-num="{{$client->national_num}}"
                          data-birthdate="{{$client->birthdate}}"
                          data-phone2="{{$client->phone2}}"
                          data-address="{{$client->address}}"
                          data-city="{{$client->city}}"
                          data-beds-num="{{$client->rooms_num}}"
                          data-family-num="{{$client->family_num}}"
                          data-children="{{$client->children}}"
                          data-job="{{$client->job}}">
                          {{$client->name}} - {{$client->phone}} - {{$client->national_num}}
                        </option>
                      @endforeach
                    @endif
                  </select>
                  <a href="{{ route('clients.create') }}" target="_blank" class="btn btn-outline-primary mt-2" title="إضافة عميل جديد"><i class="fas fa-user-plus"></i> إضافة عميل جديد</a>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="new_new_maid_id" class="form-label">اختر عاملة</label>
                  <select name="new_new_maid_id" id="new_new_maid_id" class="form-select select2" style="width:100%">
                    <option value="" selected>اختر عاملة...</option>
                    @foreach($all_available_maids as $maid)
                      <option value="{{$maid->id}}"
                        data-name="{{$maid->name}}"
                        data-passport="{{$maid->passport_num}}"
                        data-religion="{{$maid->religion ? $maid->religion->title : ''}}"
                        data-job="{{$maid->jobTitles && $maid->jobTitles->first() ? $maid->jobTitles->first()->title : ''}}"
                        data-nationality="{{$maid->nationality ? $maid->nationality->sub_title : ''}}"
                        data-salary="{{$maid->salary}}"
                        data-experience="{{$maid->experience_years}}"
                        data-age="{{$maid->age}}">
                        {{$maid->name}} - {{$maid->passport_num}} - {{$maid->nationality ? $maid->nationality->sub_title : ''}}
                      </option>
                    @endforeach
                  </select>
                  <button type="button" id="new-no-maid-btn" class="btn btn-outline-warning mt-2 w-100"><i class="fas fa-user-slash me-1"></i> بدون عاملة حالياً (تأجيل الاختيار)</button>
                </div>
              </div>
              <div id="new-no-maid-alert" style="display:none;">
                <div class="alert alert-warning mt-2">
                  <i class="fas fa-exclamation-triangle me-2"></i>
                  تم اختيار "بدون عاملة حالياً". يمكنك إضافة عاملة لاحقاً من صفحة العقود.
                </div>

                <!-- حقل اختيار جنسية العاملة المطلوبة -->
                <div class="row mt-3">
                  <div class="col-12">
                    <label for="required_maid_nationality" class="form-label">الجنسية المطلوبة للعاملة <span class="text-danger">*</span></label>
                    <select name="required_maid_nationality" id="required_maid_nationality" class="form-select">
                      <option value="">اختر الجنسية المطلوبة...</option>
                      @php
                        $maidNationalities = \App\Models\ingaz\Nationality::with('translations')->get();
                      @endphp
                      @foreach($maidNationalities as $nationality)
                        <option value="{{ $nationality->id }}">{{ $nationality->sub_title }}</option>
                      @endforeach
                    </select>
                    <div class="invalid-feedback" id="err_required_maid_nationality"></div>
                    <small class="text-muted">هذا سيساعد في العثور على العاملة المناسبة لاحقاً</small>
                  </div>
                </div>
              </div>

              <!-- بيانات العميل والعاملة (تظهر بعد الاختيار) في التبويب الجديد -->
              <div class="col-md-12">
                  <div class="card">
                    <div class="card-header bg-primary text-white py-2">
                      <h6 class="mb-0"><i class="fas fa-user me-2"></i>بيانات العميل المختار</h6>
                    </div>
                    <div class="card-body p-2">
                      <div class="row">
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.client_name")}} <span class="text-danger">*</span></label>
                          <input type="text" id="new_client_name" name="new_client_name" class="form-control" data-validation="required">
                          <div class="invalid-feedback" id="err_new_client_name"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.client_phone")}} <span class="text-danger">*</span></label>
                          <input type="tel" id="new_client_phone" name="new_client_phone" class="form-control" data-validation="required" dir="rtl">
                          <div class="invalid-feedback" id="err_new_client_phone"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                          <label for="client_nationality_id" class="form-label">{{helperTrans("ingaz.nationality_id")}} <span class="text-danger">*</span></label>
                          <select id="client_nationality_id" name="client_nationality_id" class="form-select form-control select2" data-validation="required">
                              <option value="">{{helperTrans('ingaz.choose')}}</option>
                              @foreach($countries as $country)
                                  <option value="{{$country->id}}" {{ $country->code === 'SA' ? 'selected' : '' }}>{{$country->name_ar}}</option>
                              @endforeach
                          </select>
                          <div class="invalid-feedback" id="err_client_nationality_id"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.email")}} <small class="text-muted">(اختياري)</small></label>
                          <input type="email" id="new_client_email" name="new_client_email" class="form-control">
                          <div class="invalid-feedback" id="err_new_client_email"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.national_num")}} <span class="text-danger">*</span></label>
                          <input type="text" id="new_client_national_num" name="new_client_national_num" class="form-control" data-validation="required">
                          <div class="invalid-feedback" id="err_new_client_national_num"></div>
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.birthdate")}} <span class="text-danger">*</span></label>
                          <input type="date" id="new_client_birthdate" name="new_client_birthdate" class="form-control" data-validation="required" max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.phone2")}} <span class="text-danger">*</span></label>
                          <input type="tel" id="new_client_phone2" name="new_client_phone2" class="form-control" data-validation="required">
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.address")}} <span class="text-danger">*</span></label>
                          <input type="text" id="new_client_address" name="new_client_address" class="form-control" data-validation="required">
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.city of receive")}} <span class="text-danger">*</span></label>
                          <input type="text" id="new_client_city" name="new_client_city" class="form-control" data-validation="required">
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.beds number")}} <small class="text-muted">(اختياري)</small></label>
                          <input type="text" id="new_client_beds_num" name="new_client_beds_num" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.family_number")}} <small class="text-muted">(اختياري)</small></label>
                          <input type="text" id="new_client_family_num" name="new_client_family_num" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.children")}} <small class="text-muted">(اختياري - تحت سن 12)</small></label>
                          <input type="number" id="new_client_children" name="new_client_children" class="form-control" max="50">
                        </div>
                        <div class="col-md-6 mb-2">
                          <label class="form-label">{{helperTrans("ingaz.job")}} <small class="text-muted">(اختياري)</small></label>
                          <input type="text" id="new_client_job" name="new_client_job" class="form-control">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header bg-success text-white py-2">
                      <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>بيانات العاملة المختارة</h6>
                    </div>
                    <div class="card-body p-2" id="new-maid-section-card">
                      <div class="row">
                        <div class="col-6 mb-2">
                          <label class="form-label">الاسم</label>
                          <input type="text" id="new_maid_name" name="new_maid_name" class="form-control">
                          <div class="invalid-feedback" id="err_new_maid_name"></div>
                        </div>
                        <div class="col-6 mb-2">
                          <label class="form-label">رقم الجواز</label>
                          <input type="text" id="new_maid_passport" name="new_maid_passport" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                          <label class="form-label">الديانة</label>
                          <input type="text" id="new_maid_religion" name="new_maid_religion" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                          <label class="form-label">المهنة</label>
                          <input type="text" id="new_maid_job" name="new_maid_job" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                          <label class="form-label">الجنسية</label>
                          <input type="text" id="new_maid_nationality" name="new_maid_nationality" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                          <label class="form-label">الراتب</label>
                          <input type="text" id="new_maid_salary" name="new_maid_salary" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                          <label class="form-label">سنوات الخبرة</label>
                          <input type="text" id="new_maid_experience_years" name="new_maid_experience_years" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                          <label class="form-label">العمر</label>
                          <input type="text" id="new_maid_age" name="new_maid_age" class="form-control">
                        </div>
                      </div>
                      <div id="new-no-maid-message" class="alert alert-warning mt-2" style="display:none;">
                        <i class="fas fa-user-slash me-1"></i> لا يوجد عاملة حالياً.
                      </div>
                    </div>
                  </div>
                </div>
              <!-- بيانات العقد -->
              <div class="col-12 mt-4">
                <div class="card">
                  <div class="card-header bg-info text-white">
                    <h4 class="mb-0">معلومات العقد</h4>
                  </div>
                  <div class="card-body">
                    @include('admin.ingaz.RecruitmentContract.contract-section')
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- زر الحفظ في تبويب عقد جديد بدون طلب مسبق -->
          <div class="col-12 mt-3">
            <div class="card">
              <div class="card-body text-center">
                <button class="btn btn-primary btn-lg px-5" type="submit" id="newContractSubmitBtn" onclick="console.log('Submit button clicked')">
                  <i class="fa-solid fa-floppy-disk me-2"></i>
                  {{__('buttons.save')}}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
<script>
    var indexRoute = "{{ route('RecruitmentContract.index') }}";
    var selectedOrder = @json($selectedOrder);
    var hasOrderId = {{ isset($orderId) ? 'true' : 'false' }};
</script>

<script>
$(document).ready(function() {
  // تهيئة التبويبات حسب وجود order_id
  var hasOrderId = {{ isset($orderId) ? 'true' : 'false' }};
  if (!hasOrderId) {
    $('#tab-order').removeClass('active');
    $('#tab-order-pane').removeClass('show active');
    $('#tab-new').addClass('active');
    $('#tab-new-pane').addClass('show active');
  } else {
    // إذا كان هناك order_id، تأكد من أن التبويب القديم نشط
    $('#tab-new').removeClass('active');
    $('#tab-new-pane').removeClass('show active');
    $('#tab-order').addClass('active');
    $('#tab-order-pane').addClass('show active');
  }

  // تهيئة Select2
  $('#new_existing_client_id').select2({
    placeholder: 'اختر عميل...',
    allowClear: true,
    dir: 'rtl',
    width: '100%',
    language: {
      noResults: function() { return "لا توجد نتائج"; },
      searching: function() { return "جاري البحث..."; }
    }
  });
  $('#new_new_maid_id').select2({
    placeholder: 'اختر عاملة...',
    allowClear: true,
    dir: 'rtl',
    width: '100%',
    language: {
      noResults: function() { return "لا توجد نتائج"; },
      searching: function() { return "جاري البحث..."; }
    }
  });

  // إضافة تهيئة إضافية للتأكد من عمل Select2 بشكل صحيح
  $('#client_nationality_id').select2({
    placeholder: 'اختر الجنسية...',
    allowClear: true,
    dir: 'rtl',
    width: '100%'
  });

    // تهيئة حقل تاريخ الميلاد
  $('#new_client_birthdate').on('change', function() {
    var selectedDate = new Date($(this).val());
    var today = new Date();

    if (selectedDate > today) {
      alert('لا يمكن اختيار تاريخ في المستقبل');
      $(this).val('');
    }

    // طباعة التاريخ المختار للتأكد
    console.log('Selected birthdate:', $(this).val());
  });

  // زر بدون عاملة
  $('#new-no-maid-btn').on('click', function() {
    $('#new_new_maid_id').val('').trigger('change');
    $('#new-no-maid-alert').fadeIn(300);
  });
  $('#new_new_maid_id').on('change', function() {
    if ($(this).val()) {
      $('#new-no-maid-alert').fadeOut(200);
    }
  });

  // إعادة تهيئة Select2 بعد تحميل الصفحة للتأكد من عمل البحث
  setTimeout(function() {
    if ($.fn.select2) {
      $('#new_existing_client_id').select2('destroy').select2({
        placeholder: 'اختر عميل...',
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
          noResults: function() { return "لا توجد نتائج"; },
          searching: function() { return "جاري البحث..."; }
        }
      });

      $('#new_new_maid_id').select2('destroy').select2({
        placeholder: 'اختر عاملة...',
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
          noResults: function() { return "لا توجد نتائج"; },
          searching: function() { return "جاري البحث..."; }
        }
      });
    }
  }, 500);
});
</script>

<script>
    $(document).ready(function() {
        // Hide both client type options if we have an orderId
        if (hasOrderId) {
            $('#new_client').parent().hide();
            $('#ex_client').parent().hide();
            $('#client_type').val('ex_client'); // Set client type to ex_client when orderId is present

            // Set maid_id from selected order
            if (selectedOrder && selectedOrder.maid) {
                $('#maid_id').val(selectedOrder.maid.id);
            }

            // Lock all maid data fields and make them read-only
            $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true).css('background-color', '#e9ecef');

            // Prevent any changes to maid data
            $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').on('change keyup', function(e) {
                e.preventDefault();
                return false;
            });
        }

        // Initialize Select2
        $('.select2').select2({
            placeholder: 'Search for an order...',
            allowClear: true
        });

        // If we have a selected order, populate the form fields
        if (selectedOrder) {
            console.log('Selected order data:', selectedOrder);

            // Set the order in the dropdown
            $('#order_id').val(selectedOrder.id).trigger('change');

            // Fill in client data
            if (selectedOrder.user) {
                $('#client_name').val(selectedOrder.user.name);
                $('#client_phone').val(selectedOrder.user.phone);
                if (selectedOrder.user.nationality_id) {
                    $('#client_nationality_id').val(selectedOrder.user.nationality_id).trigger('change');
                }
            }

            // Set default contract duration to 24 months
            $('#contract_duration').val('2');

            // Fill in maid data
            if (selectedOrder.maid) {
                // Set maid name directly from the order data
                $('#maid_name').val(selectedOrder.maid.name);
                $('#maid_passport').val(selectedOrder.maid.passport_num);

                // Fetch maid details to get proper names/values
                if (selectedOrder.maid.id) {
                    $.ajax({
                        url: '/admin/maids/' + selectedOrder.maid.id + '/details',
                        type: 'GET',
                        success: function(data) {
                            console.log('Maid details response:', data);

                            // Set religion
                            if (data.religion_title) {
                                $('#maid_religion').val(data.religion_title);
                            }

                            // Handle job type - use the actual job type from maid data
                            if (data.job_types && data.job_types.length > 0) {
                                $('#maid_job').val(data.job_types[0].title);
                            }

                            // Handle nationality
                            if (data.nationality && data.nationality.title) {
                                $('#maid_nationality').val(data.nationality.title);
                            }

                            // Set other fields
                            $('#maid_salary').val(data.salary || '');
                            $('#experience_years').val(data.experience_years || '');
                            $('#age').val(data.age || '');

                            // Force update of Select2 if it exists
                            if ($.fn.select2) {
                                $('#maid_nationality').select2('destroy').select2();
                                $('#maid_religion').select2('destroy').select2();
                                $('#maid_job').select2('destroy').select2();
                            }
                        },
                        error: function(error) {
                            console.error('Error fetching maid details:', error);
                        }
                    });
                }
            }

            // Set client type to existing client
            $('#ex_client').prop('checked', true).trigger('change');
        }

        $('#client_type').change(function () {
            if ($(this).val() === 'new_client') {
                $('#new-client-section').show();
                $('#order-selection').hide();
                $('#maid-selection').show();
                $('#maid_id').prop('required', true);
                $('#order_id').prop('required', false);
                $('#order_id').val('').trigger('change');
            } else {
                $('#new-client-section').hide();
                $('#order-selection').show();
                $('#maid-selection').hide();
                $('#maid_id').prop('required', false);
                $('#order_id').prop('required', true);
            }
        });

        $('#maid_id').change(function () {
            var maidId = $(this).val();
            if (maidId) {
                var selectedOption = $(this).find('option:selected');
                console.log('Selected maid data:', {
                    name: selectedOption.data('name'),
                    passport: selectedOption.data('passport'),
                    religion: selectedOption.data('religion'),
                    job: selectedOption.data('job'),
                    nationality: selectedOption.data('nationality'),
                    salary: selectedOption.data('salary'),
                    experience: selectedOption.data('experience'),
                    age: selectedOption.data('age')
                });

                // Enable fields temporarily
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', false);

                // Set basic information from data attributes
                $('#maid_name').val(selectedOption.data('name'));
                $('#maid_passport').val(selectedOption.data('passport'));
                $('#maid_religion').val(selectedOption.data('religion'));
                $('#maid_job').val(selectedOption.data('job'));
                $('#maid_nationality').val(selectedOption.data('nationality'));
                $('#maid_salary').val(selectedOption.data('salary'));
                $('#experience_years').val(selectedOption.data('experience'));
                $('#age').val(selectedOption.data('age'));

                // Force update of Select2 if it exists
                if ($.fn.select2) {
                    $('#maid_nationality').select2('destroy').select2();
                    $('#maid_religion').select2('destroy').select2();
                    $('#maid_job').select2('destroy').select2();
                }

                // Disable fields again after setting values
                setTimeout(function() {
                    $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true);
                }, 100);
            }
        });

        // Handle order selection change
        $('#order_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var maidId = selectedOption.data('maid-id');

            // Set client type to ex_client when an order is selected
            $('#client_type').val('ex_client');

            // Set maid_id from selected order
            $('#maid_id').val(maidId);

            // Clear all fields first
            $('#client_name, #client_phone, #client_nationality_id, #maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').val('');

            if (maidId) {
                // Fill in the client form fields
                $('#client_name').val(selectedOption.data('client-name'));
                $('#client_phone').val(selectedOption.data('client-phone'));
                if (selectedOption.data('client-nationality')) {
                    $('#client_nationality_id').val(selectedOption.data('client-nationality')).trigger('change');
                }

                // Fill in the maid form fields
                $('#maid_name').val(selectedOption.data('maid-name'));
                $('#maid_passport').val(selectedOption.data('maid-passport'));

                // Fetch maid details to get proper names/values
                $.ajax({
                    url: '/admin/maids/' + maidId + '/details',
                    type: 'GET',
                    success: function(data) {
                        console.log('Maid details response:', data);

                        // Enable fields temporarily
                        $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', false);

                        // Set religion
                        console.log('Setting religion:', data.religion_title);
                        $('#maid_religion').val(data.religion_title || '');

                        // Handle job type
                        var jobTypeTitle = '';
                        if (data.job_types && data.job_types.length > 0) {
                            jobTypeTitle = data.job_types[0].title || '';
                            console.log('Setting job type:', jobTypeTitle);
                        }
                        $('#maid_job').val(jobTypeTitle);

                        // Handle nationality
                        var nationalityTitle = '';
                        if (data.nationality) {
                            nationalityTitle = data.nationality.title || '';
                            console.log('Setting nationality:', nationalityTitle);
                        }
                        $('#maid_nationality').val(nationalityTitle);

                        // Set other fields
                        console.log('Setting salary:', data.salary);
                        $('#maid_salary').val(data.salary || '');

                        console.log('Setting experience years:', data.experience_years);
                        $('#experience_years').val(data.experience_years || '');

                        console.log('Setting age:', data.age);
                        $('#age').val(data.age || '');

                        // Force update of Select2 if it exists
                        if ($.fn.select2) {
                            try {
                            $('#maid_nationality').select2('destroy').select2();
                            $('#maid_religion').select2('destroy').select2();
                            $('#maid_job').select2('destroy').select2();
                            } catch (e) {
                                console.log('Select2 error:', e);
                            }
                        }

                        // Disable fields again after setting values
                        setTimeout(function() {
                            $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true);
                        }, 100);
                    },
                    error: function(error) {
                        console.error('Error fetching maid details:', error);
                    }
                });
            }
        });

        // عند اختيار عميل في التبويب الجديد: عبئ بيانات العميل (حقول منفصلة)
        $('#new_existing_client_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                // Debug: طباعة بيانات العميل المختار
                console.log('Selected client data:', {
                    name: selectedOption.data('name'),
                    phone: selectedOption.data('phone'),
                    email: selectedOption.data('email'),
                    nationality: selectedOption.data('nationality'),
                    national_num: selectedOption.data('national-num'),
                    birthdate: selectedOption.data('birthdate'),
                    phone2: selectedOption.data('phone2'),
                    address: selectedOption.data('address'),
                    city: selectedOption.data('city'),
                    beds_num: selectedOption.data('beds-num'),
                    family_num: selectedOption.data('family-num'),
                    children: selectedOption.data('children'),
                    job: selectedOption.data('job')
                });

                // تعبئة الحقول المطلوبة
                $('#new_client_name').val(selectedOption.data('name'));
                $('#new_client_phone').val(selectedOption.data('phone'));
                $('#new_client_email').val(selectedOption.data('email'));
                $('#new_client_national_num').val(selectedOption.data('national-num'));
                $('#new_client_birthdate').val(selectedOption.data('birthdate'));
                $('#new_client_phone2').val(selectedOption.data('phone2'));
                $('#new_client_address').val(selectedOption.data('address'));
                $('#new_client_city').val(selectedOption.data('city'));

                // تعبئة الحقول الاختيارية
                $('#new_client_beds_num').val(selectedOption.data('beds-num'));
                $('#new_client_family_num').val(selectedOption.data('family-num'));
                $('#new_client_children').val(selectedOption.data('children'));
                $('#new_client_job').val(selectedOption.data('job'));

                // تعبئة حقل الجنسية في dropdown
                if (selectedOption.data('nationality')) {
                    $('#client_nationality_id').val(selectedOption.data('nationality')).trigger('change');
                    console.log('Setting client nationality to:', selectedOption.data('nationality'));
                } else {
                    console.log('No nationality data found for client');
                }

                // Debug: طباعة القيم بعد التعبئة
                console.log('Filled client data:', {
                    name: $('#new_client_name').val(),
                    phone: $('#new_client_phone').val(),
                    email: $('#new_client_email').val(),
                    nationality: $('#client_nationality_id').val(),
                    national_num: $('#new_client_national_num').val(),
                    birthdate: $('#new_client_birthdate').val(),
                    phone2: $('#new_client_phone2').val(),
                    address: $('#new_client_address').val(),
                    city: $('#new_client_city').val(),
                    beds_num: $('#new_client_beds_num').val(),
                    family_num: $('#new_client_family_num').val(),
                    children: $('#new_client_children').val(),
                    job: $('#new_client_job').val()
                });
            } else {
                // مسح جميع الحقول عند عدم اختيار عميل
                $('#new_client_name, #new_client_phone, #new_client_email, #new_client_national_num, #new_client_birthdate, #new_client_phone2, #new_client_address, #new_client_city, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job').val('');
                $('#client_nationality_id').val('').trigger('change');
            }
        });
        // عند اختيار عاملة في التبويب الجديد: عبئ بيانات العاملة (حقول منفصلة)
        $('#new_new_maid_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#new-no-maid-message').hide();

                // Debug: طباعة البيانات المختارة
                console.log('Selected maid data:', {
                    name: selectedOption.data('name'),
                    passport: selectedOption.data('passport'),
                    religion: selectedOption.data('religion'),
                    job: selectedOption.data('job'),
                    nationality: selectedOption.data('nationality'),
                    salary: selectedOption.data('salary'),
                    experience: selectedOption.data('experience'),
                    age: selectedOption.data('age')
                });

                // طباعة البيانات الخام من data attributes
                console.log('Raw data attributes:', {
                    'data-name': selectedOption.attr('data-name'),
                    'data-passport': selectedOption.attr('data-passport'),
                    'data-religion': selectedOption.attr('data-religion'),
                    'data-job': selectedOption.attr('data-job'),
                    'data-nationality': selectedOption.attr('data-nationality'),
                    'data-salary': selectedOption.attr('data-salary'),
                    'data-experience': selectedOption.attr('data-experience'),
                    'data-age': selectedOption.attr('data-age')
                });

                // طباعة تفاصيل المهنة
                console.log('Job data details:', {
                    'job-attr': selectedOption.attr('data-job'),
                    'job-data': selectedOption.data('job'),
                    'job-value': selectedOption.data('job') || 'No job data'
                });

                // طباعة تفاصيل المهنة من jobTitles
                console.log('JobTitles details:', {
                    'jobTitles-count': selectedOption.data('job') ? 'Has job title' : 'No job title',
                    'job-title-value': selectedOption.data('job')
                });

                $('#new_maid_name').val(selectedOption.data('name'));
                $('#new_maid_passport').val(selectedOption.data('passport'));
                $('#new_maid_religion').val(selectedOption.data('religion'));
                $('#new_maid_job').val(selectedOption.data('job'));
                console.log('Setting maid job to:', selectedOption.data('job'));
                console.log('Maid job field value after setting:', $('#new_maid_job').val());
                console.log('Job title from jobTitles:', selectedOption.data('job'));
                $('#new_maid_nationality').val(selectedOption.data('nationality'));
                console.log('Setting maid nationality to:', selectedOption.data('nationality'));
                console.log('Maid nationality field value after setting:', $('#new_maid_nationality').val());
                $('#new_maid_salary').val(selectedOption.data('salary'));
                $('#new_maid_experience_years').val(selectedOption.data('experience'));
                $('#new_maid_age').val(selectedOption.data('age'));

                // Debug: طباعة القيم بعد التعبئة
                console.log('Filled nationality field:', $('#new_maid_nationality').val());
                console.log('Filled job field:', $('#new_maid_job').val());
                console.log('Filled religion field:', $('#new_maid_religion').val());
                console.log('All maid fields after filling:', {
                    name: $('#new_maid_name').val(),
                    passport: $('#new_maid_passport').val(),
                    religion: $('#new_maid_religion').val(),
                    job: $('#new_maid_job').val(),
                    nationality: $('#new_maid_nationality').val(),
                    salary: $('#new_maid_salary').val(),
                    experience: $('#new_maid_experience_years').val(),
                    age: $('#new_maid_age').val()
                });

                // طباعة تفاصيل إضافية عن المهنة
                console.log('Job field details:', {
                    'field-id': '#new_maid_job',
                    'field-value': $('#new_maid_job').val(),
                    'field-visible': $('#new_maid_job').is(':visible'),
                    'field-disabled': $('#new_maid_job').is(':disabled')
                });
            } else {
                $('#new_maid_name, #new_maid_passport, #new_maid_religion, #new_maid_job, #new_maid_nationality, #new_maid_salary, #new_maid_experience_years, #new_maid_age').val('');
            }
        });
        // زر بدون عاملة في التبويب الجديد
        $('#new-no-maid-btn').on('click', function() {
            $('#new_new_maid_id').val('').trigger('change');
            $('#new-no-maid-message').show();
            $('#new_maid_name, #new_maid_passport, #new_maid_religion, #new_maid_job, #new_maid_nationality, #new_maid_salary, #new_maid_experience_years, #new_maid_age').val('');
        });

        // // Initialize Select2 for maid_id with custom template
        // if ($.fn.select2) {
        //     $('#maid_id').select2({
        //         placeholder: 'اختر العاملة',
        //         allowClear: true,
        //         templateResult: formatMaidOption,
        //         templateSelection: formatMaidOption
        //     });
        // }

        function formatMaidOption(maid) {
            if (!maid.id) return maid.text;
            var $maid = $(maid.element);
            var $container = $(
                '<div class="select2-result-maid">' +
                    '<div class="select2-result-maid__name">' + $maid.data('name') + '</div>' +
                    '<div class="select2-result-maid__details">' +
                        'رقم الجواز: ' + $maid.data('passport') + ' | ' +
                        'الجنسية: ' + $maid.data('nationality') +
                    '</div>' +
                '</div>'
            );
            return $container;
        }



        // معالج للنموذج القديم (عقد من طلب مسبق)
        $('#maidForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Old form submission started');

            // تحقق من التبويب النشط
            var isOrderTab = $('#tab-order-pane').hasClass('show active');
            var isNewTab = $('#tab-new-pane').hasClass('show active');
            console.log('Order tab active:', isOrderTab);
            console.log('New tab active:', isNewTab);

            // إذا كان التبويب الجديد نشط، لا نرسل الفورم القديم
            if (isNewTab) {
                console.log('New tab active, stopping old form submission');
                return false;
            }

            // Clear previous validation errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let isValid = true;
            let firstInvalidField = null;

            // Validate required fields for order tab
            $('#tab-order-pane [data-validation="required"]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');

                    // Add error message
                    var fieldName = $(this).attr('name');
                    var errorMessage = '';

                    switch(fieldName) {
                        case 'contract_date':
                            errorMessage = 'يرجى ادخال تاريخ العقد';
                            break;
                        case 'msaned_number':
                            errorMessage = 'يرجى ادخال رقم مساند';
                            break;
                        case 'visa_number':
                            errorMessage = 'يرجى ادخال رقم التأشيرة';
                            break;
                        case 'contract_duration':
                            errorMessage = 'يرجى ادخال مدة العقد';
                            break;
                        case 'order_id':
                            errorMessage = 'يرجى اختيار الطلب';
                            break;
                        default:
                            errorMessage = 'هذا الحقل مطلوب';
                    }

                    $(this).after('<div class="invalid-feedback">' + errorMessage + '</div>');

                    if (!firstInvalidField) {
                        firstInvalidField = $(this);
                    }
                }
            });

            // Additional validation for maid_id - allow postponing maid selection
            if (!$('#maid_id').val()) {
                // Check if this is a new contract tab where maid selection can be postponed
                var isNewContractTab = $('#tab-new-pane').hasClass('show active');
                if (isNewContractTab) {
                    // For new contracts, maid selection can be postponed
                    console.log('Maid selection postponed for new contract');
                } else {
                    // For existing orders, maid is required
                isValid = false;
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'يرجى اختيار العاملة',
                    confirmButtonText: 'حسناً'
                });
                return false;
                }
            }

            if (!isValid) {
                // Scroll to first invalid field
                if (firstInvalidField) {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 100
                    }, 500);
                }
                return false;
            }

            // If all validations pass, submit the form via AJAX
            let formData = new FormData(this);

            // Debug: طباعة البيانات المرسلة
            console.log('Form data being sent:');
            for (var pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            let url = $(this).attr('action');
            let submitButton = $(e.originalEvent.submitter);

            // Disable submit button and show loading state
            submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Server response:', response);
                    if (response.success) {
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: response.message || 'تم إنشاء العقد بنجاح',
                            confirmButtonText: 'حسناً'
                        }).then((result) => {
                            window.location.href = indexRoute;
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: response.message || 'حدث خطأ أثناء إنشاء العقد',
                            confirmButtonText: 'حسناً'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });

                    let errorMessage = 'حدث خطأ أثناء إنشاء العقد';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            // Handle validation errors
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: errorMessage,
                        confirmButtonText: 'حسناً'
                    });
                },
                complete: function() {
                    // Re-enable submit button
                    submitButton.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>حفظ');
                }
            });
        });
    });
</script>

<script>
// Global error handler
window.addEventListener('error', function(e) {
  console.error('JavaScript error:', e.error);
});

$(document).ready(function() {
  console.log('Document ready - New Contract Form initialized');
  console.log('Form elements found:', {
    newContractForm: $('#newContractForm').length,
    newContractSubmitBtn: $('#newContractSubmitBtn').length,
    contractDate: $('#contract_date').length,
    msanedNumber: $('#msaned_number').length,
    visaNumber: $('#visa_number').length
  });

  console.log('Contract fields in new tab:', {
    contractDate: $('#tab-new-pane #contract_date').length,
    msanedNumber: $('#tab-new-pane #msaned_number').length,
    visaNumber: $('#tab-new-pane #visa_number').length,
    contractDuration: $('#tab-new-pane #contract_duration').length
  });

  // Check if form exists
  if ($('#newContractForm').length === 0) {
    console.error('newContractForm not found in DOM');
  } else {
    console.log('newContractForm found in DOM');
  }

  // Check which tab is active
  console.log('Active tab:', {
    tabOrderActive: $('#tab-order').hasClass('active'),
    tabNewActive: $('#tab-new').hasClass('active'),
    tabOrderPaneActive: $('#tab-order-pane').hasClass('show active'),
    tabNewPaneActive: $('#tab-new-pane').hasClass('show active')
  });

  // معالجة حفظ العقد الجديد بدون طلب مسبق
  console.log('Setting up form submission handler');
  console.log('jQuery version:', $.fn.jquery);
  console.log('Form element:', $('#newContractForm')[0]);

  $('#newContractForm').on('submit', function(e) {
    console.log('Form submit event triggered');
    e.preventDefault();
    console.log('Form submission started');

    // تحقق من التبويب النشط
    var isNewContractTab = $('#tab-new-pane').hasClass('show active');
    console.log('Active tab check:', {
      isNewContractTab: isNewContractTab,
      tabNewPaneActive: $('#tab-new-pane').hasClass('show active'),
      tabOrderPaneActive: $('#tab-order-pane').hasClass('show active')
    });

    // تحقق من وجود عميل
    var clientId = $('#new_existing_client_id').val();
    var maidId = $('#new_new_maid_id').val();
    var contractType = 'new_contract';
    var isValid = true;

    console.log('Initial values:', {
      clientId: clientId,
      maidId: maidId,
      contractType: contractType
    });

    // التحقق من الحقول فقط إذا كان التبويب الجديد نشط
    if (isNewContractTab) {
      // تحقق الاسم ثلاثي
      var clientName = $('#new_client_name').val().trim();
      console.log('Validating client name:', clientName);
      if (!clientName) {
        isValid = false;
        $('#new_client_name').addClass('is-invalid');
        $('#err_new_client_name').text('يرجى ادخال اسم العميل');
        console.log('Client name validation failed: empty');
      } else if (clientName.split(' ').length < 3) {
        isValid = false;
        $('#new_client_name').addClass('is-invalid');
        $('#err_new_client_name').text('يجب أن يكون الاسم ثلاثي على الأقل');
        console.log('Client name validation failed: not three parts');
      } else {
        $('#new_client_name').removeClass('is-invalid');
        $('#err_new_client_name').text('');
        console.log('Client name validation passed');
      }

    // تحقق رقم الجوال
    var clientPhone = $('#new_client_phone').val().trim();
    console.log('Validating client phone:', clientPhone);
    if (!clientPhone) {
      isValid = false;
      $('#new_client_phone').addClass('is-invalid');
      $('#err_new_client_phone').text('يرجى ادخال رقم الجوال');
      console.log('Client phone validation failed: empty');
    } else if (!/^05|^5\d{8}$/.test(clientPhone)) {
      isValid = false;
      $('#new_client_phone').addClass('is-invalid');
      $('#err_new_client_phone').text('رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام');
      console.log('Client phone validation failed: invalid format');
    } else {
      $('#new_client_phone').removeClass('is-invalid');
      $('#err_new_client_phone').text('');
      console.log('Client phone validation passed');
    }

    // تحقق رقم الهوية
    var clientNationalityNum = $('#new_client_national_num').val().trim();
    console.log('Validating client nationality number:', clientNationalityNum);
    if (!clientNationalityNum) {
      isValid = false;
      $('#new_client_national_num').addClass('is-invalid');
      $('#err_new_client_national_num').text('يرجى ادخال رقم الهوية');
      console.log('Client nationality number validation failed: empty');
    } else if (!/^\d{10}$/.test(clientNationalityNum)) {
      isValid = false;
      $('#new_client_national_num').addClass('is-invalid');
      $('#err_new_client_national_num').text('رقم الهوية يجب أن يكون 10 أرقام');
      console.log('Client nationality number validation failed: invalid format');
    } else {
      $('#new_client_national_num').removeClass('is-invalid');
      $('#err_new_client_national_num').text('');
      console.log('Client nationality number validation passed');
    }

    // تحقق تاريخ الميلاد
    var clientBirthdate = $('#new_client_birthdate').val();
    if (!clientBirthdate) {
      isValid = false;
      $('#new_client_birthdate').addClass('is-invalid');
      $('<div class="invalid-feedback">يرجى ادخال تاريخ الميلاد</div>').insertAfter('#new_client_birthdate');
    } else {
      $('#new_client_birthdate').removeClass('is-invalid');
      $('#new_client_birthdate').next('.invalid-feedback').remove();
    }

    // تحقق رقم الهاتف الإضافي
    var clientPhone2 = $('#new_client_phone2').val().trim();
    if (!clientPhone2) {
      isValid = false;
      $('#new_client_phone2').addClass('is-invalid');
      $('<div class="invalid-feedback">يرجى ادخال رقم الهاتف الإضافي</div>').insertAfter('#new_client_phone2');
    } else {
      $('#new_client_phone2').removeClass('is-invalid');
      $('#new_client_phone2').next('.invalid-feedback').remove();
    }

    // تحقق العنوان
    var clientAddress = $('#new_client_address').val().trim();
    if (!clientAddress) {
      isValid = false;
      $('#new_client_address').addClass('is-invalid');
      $('<div class="invalid-feedback">يرجى ادخال العنوان</div>').insertAfter('#new_client_address');
    } else {
      $('#new_client_address').removeClass('is-invalid');
      $('#new_client_address').next('.invalid-feedback').remove();
    }

    // تحقق المدينة
    var clientCity = $('#new_client_city').val().trim();
    if (!clientCity) {
      isValid = false;
      $('#new_client_city').addClass('is-invalid');
      $('<div class="invalid-feedback">يرجى ادخال المدينة</div>').insertAfter('#new_client_city');
    } else {
      $('#new_client_city').removeClass('is-invalid');
      $('#new_client_city').next('.invalid-feedback').remove();
    }

    // تحقق البريد الإلكتروني (اختياري)
    var clientEmail = $('#new_client_email').val().trim();
    if (clientEmail && !/^\S+@\S+\.\S+$/.test(clientEmail)) {
      isValid = false;
      $('#new_client_email').addClass('is-invalid');
      $('#err_new_client_email').text('صيغة البريد الإلكتروني غير صحيحة');
    } else {
      $('#new_client_email').removeClass('is-invalid');
      $('#err_new_client_email').text('');
    }

    // تحقق الجنسية (مطلوب في التبويب الجديد)
    var clientNationalityId = $('#client_nationality_id').val();
    console.log('Validating client nationality:', clientNationalityId);
    if (!clientNationalityId) {
      isValid = false;
      $('#client_nationality_id').addClass('is-invalid');
      $('#err_client_nationality_id').text('يرجى اختيار الجنسية');
      console.log('Client nationality validation failed: empty');
    } else {
      $('#client_nationality_id').removeClass('is-invalid');
      $('#err_client_nationality_id').text('');
      console.log('Client nationality validation passed');
    }

    // تحقق الجنسية المطلوبة للعاملة (فقط إذا لم يتم اختيار عاملة)
    var isNoMaidSelected = $('#new-no-maid-alert').is(':visible');
    if (isNoMaidSelected) {
      var requiredMaidNationality = $('#required_maid_nationality').val();
      console.log('Validating required maid nationality:', requiredMaidNationality);
      if (!requiredMaidNationality) {
        isValid = false;
        $('#required_maid_nationality').addClass('is-invalid');
        $('#err_required_maid_nationality').text('يرجى اختيار الجنسية المطلوبة للعاملة');
        console.log('Required maid nationality validation failed: empty');
      } else {
        $('#required_maid_nationality').removeClass('is-invalid');
        $('#err_required_maid_nationality').text('');
        console.log('Required maid nationality validation passed');
      }
    }

    } // إغلاق شرط isNewContractTab

    // التحقق من الحقول المطلوبة للعقد (في كلا التبويبين)
    var contractDate, msanedNumber, visaNumber;

    if (isNewContractTab) {
      contractDate = $('#tab-new-pane #contract_date').val();
      msanedNumber = $('#tab-new-pane #msaned_number').val();
      visaNumber = $('#tab-new-pane #visa_number').val();
    } else {
      contractDate = $('#tab-order-pane #contract_date').val();
      msanedNumber = $('#tab-order-pane #msaned_number').val();
      visaNumber = $('#tab-order-pane #visa_number').val();
    }

    console.log('Validating contract fields:', {
      contractDate: contractDate,
      msanedNumber: msanedNumber,
      visaNumber: visaNumber
    });

    if (!contractDate) {
      isValid = false;
      if (isNewContractTab) {
        $('#tab-new-pane #contract_date').addClass('is-invalid');
        $('<div class="invalid-feedback">يرجى ادخال تاريخ العقد</div>').insertAfter('#tab-new-pane #contract_date');
      } else {
        $('#tab-order-pane #contract_date').addClass('is-invalid');
        $('<div class="invalid-feedback">يرجى ادخال تاريخ العقد</div>').insertAfter('#tab-order-pane #contract_date');
      }
      console.log('Contract date validation failed: empty');
    } else {
      if (isNewContractTab) {
        $('#tab-new-pane #contract_date').removeClass('is-invalid');
        $('#tab-new-pane #contract_date').next('.invalid-feedback').remove();
      } else {
        $('#tab-order-pane #contract_date').removeClass('is-invalid');
        $('#tab-order-pane #contract_date').next('.invalid-feedback').remove();
      }
      console.log('Contract date validation passed');
    }

    if (!msanedNumber) {
      isValid = false;
      if (isNewContractTab) {
        $('#tab-new-pane #msaned_number').addClass('is-invalid');
        $('<div class="invalid-feedback">يرجى ادخال رقم مساند</div>').insertAfter('#tab-new-pane #msaned_number');
      } else {
        $('#tab-order-pane #msaned_number').addClass('is-invalid');
        $('<div class="invalid-feedback">يرجى ادخال رقم مساند</div>').insertAfter('#tab-order-pane #msaned_number');
      }
      console.log('Msaned number validation failed: empty');
    } else {
      if (isNewContractTab) {
        $('#tab-new-pane #msaned_number').removeClass('is-invalid');
        $('#tab-new-pane #msaned_number').next('.invalid-feedback').remove();
      } else {
        $('#tab-order-pane #msaned_number').removeClass('is-invalid');
        $('#tab-order-pane #msaned_number').next('.invalid-feedback').remove();
      }
      console.log('Msaned number validation passed');
    }

    if (!visaNumber) {
      isValid = false;
      if (isNewContractTab) {
        $('#tab-new-pane #visa_number').addClass('is-invalid');
        $('<div class="invalid-feedback">يرجى ادخال رقم التأشيرة</div>').insertAfter('#tab-new-pane #visa_number');
      } else {
        $('#tab-order-pane #visa_number').addClass('is-invalid');
        $('<div class="invalid-feedback">يرجى ادخال رقم التأشيرة</div>').insertAfter('#tab-order-pane #visa_number');
      }
      console.log('Visa number validation failed: empty');
    } else {
      if (isNewContractTab) {
        $('#tab-new-pane #visa_number').removeClass('is-invalid');
        $('#tab-new-pane #visa_number').next('.invalid-feedback').remove();
      } else {
        $('#tab-order-pane #visa_number').removeClass('is-invalid');
        $('#tab-order-pane #visa_number').next('.invalid-feedback').remove();
      }
      console.log('Visa number validation passed');
    }

    // التحقق من المسوق
    var marketerId = '';
    if (isNewContractTab) {
      marketerId = $('#tab-new-pane #marketer_id').val();
    } else {
      marketerId = $('#tab-order-pane #marketer_id').val();
    }

    if (!marketerId) {
      isValid = false;
      if (isNewContractTab) {
        $('#tab-new-pane #marketer_id').addClass('is-invalid');
        $('#tab-new-pane #err_marketer_id').text('يرجى اختيار المسوق');
      } else {
        $('#tab-order-pane #marketer_id').addClass('is-invalid');
        $('#tab-order-pane #err_marketer_id').text('يرجى اختيار المسوق');
      }
      console.log('Marketer validation failed: empty');
    } else {
      if (isNewContractTab) {
        $('#tab-new-pane #marketer_id').removeClass('is-invalid');
        $('#tab-new-pane #err_marketer_id').text('');
      } else {
        $('#tab-order-pane #marketer_id').removeClass('is-invalid');
        $('#tab-order-pane #err_marketer_id').text('');
      }
      console.log('Marketer validation passed');
    }

    console.log('Validation completed. isValid:', isValid);
    console.log('All field values:', {
      clientName: $('#new_client_name').val(),
      clientPhone: $('#new_client_phone').val(),
      clientNationalityNum: $('#new_client_national_num').val(),
      clientBirthdate: $('#new_client_birthdate').val(),
      clientPhone2: $('#new_client_phone2').val(),
      clientAddress: $('#new_client_address').val(),
      clientCity: $('#new_client_city').val(),
      contractDate: $('#tab-new-pane #contract_date').val(),
      msanedNumber: $('#tab-new-pane #msaned_number').val(),
      visaNumber: $('#tab-new-pane #visa_number').val(),
      clientNationalityId: $('#client_nationality_id').val()
    });

    if (!isValid) {
      console.log('Validation failed, stopping submission');
      return false;
    }

    console.log('Validation passed, preparing form data');

    // تجهيز البيانات للإرسال
    var formData = new FormData();
    var csrfToken = $('input[name=_token]').val();
    console.log('CSRF Token:', csrfToken);
    formData.append('_token', csrfToken);
    formData.append('contract_type', contractType);
    formData.append('client_type', 'new_client');
    formData.append('client_id', clientId);
    formData.append('maid_id', maidId ? maidId : '');

    console.log('Basic form data prepared:', {
      contract_type: contractType,
      client_type: 'new_client',
      client_id: clientId,
      maid_id: maidId
    });

    // أضف بيانات العميل حسب التبويب النشط
    if (isNewContractTab) {
      formData.append('client_name', clientName);
      formData.append('client_phone', clientPhone);
      formData.append('client_email', clientEmail);
      formData.append('client_nationality_id', $('#client_nationality_id').val() || '');
      formData.append('new_client_national_num', clientNationalityNum);
      formData.append('new_client_birthdate', $('#new_client_birthdate').val());
      formData.append('new_client_phone2', $('#new_client_phone2').val());
      formData.append('new_client_address', $('#new_client_address').val());
      formData.append('new_client_city', $('#new_client_city').val());
      formData.append('new_client_beds_num', $('#new_client_beds_num').val());
      formData.append('new_client_family_num', $('#new_client_family_num').val());
      formData.append('new_client_children', $('#new_client_children').val());
      formData.append('new_client_job', $('#new_client_job').val());
    } else {
      // للتبويب القديم، استخدم البيانات من الطلب المسبق
      var selectedOrder = $('#order_id').val();
      formData.append('order_id', selectedOrder);
      formData.append('client_type', 'ex_client');
    }

    console.log('Client data prepared:', {
      isNewContractTab: isNewContractTab,
      client_name: isNewContractTab ? clientName : 'From existing order',
      client_phone: isNewContractTab ? clientPhone : 'From existing order',
      client_email: isNewContractTab ? clientEmail : 'From existing order',
      client_nationality_id: isNewContractTab ? $('#client_nationality_id').val() : 'From existing order',
      client_nationality_num: isNewContractTab ? clientNationalityNum : 'From existing order',
      client_birthdate: isNewContractTab ? $('#new_client_birthdate').val() : 'From existing order',
      client_phone2: isNewContractTab ? $('#new_client_phone2').val() : 'From existing order',
      client_address: isNewContractTab ? $('#new_client_address').val() : 'From existing order',
      client_city: isNewContractTab ? $('#new_client_city').val() : 'From existing order'
    });

    // أضف بيانات العاملة حسب التبويب النشط
    if (isNewContractTab) {
      var maidId = $('#new_new_maid_id').val();
      if (maidId) {
        // إذا تم اختيار عاملة
        formData.append('maid_name', $('#new_maid_name').val());
        formData.append('maid_passport', $('#new_maid_passport').val());
        formData.append('maid_religion', $('#new_maid_religion').val());
        formData.append('maid_job', $('#new_maid_job').val());
        formData.append('maid_nationality', $('#new_maid_nationality').val());
        formData.append('maid_salary', $('#new_maid_salary').val());
        formData.append('maid_experience_years', $('#new_maid_experience_years').val());
        formData.append('maid_age', $('#new_maid_age').val());
        formData.append('maid_selection_postponed', '0'); // تم اختيار عاملة
      } else {
        // إذا تم تأجيل اختيار العاملة
        formData.append('maid_selection_postponed', '1'); // تم تأجيل اختيار العاملة
        formData.append('maid_name', '');
        formData.append('maid_passport', '');
        formData.append('maid_religion', '');
        formData.append('maid_job', '');
        formData.append('maid_nationality', '');
        formData.append('maid_salary', '');
        formData.append('maid_experience_years', '');
        formData.append('maid_age', '');
      }
    } else {
      // للتبويب القديم، استخدم maid_id من الطلب المسبق
      var selectedMaidId = $('#maid_id').val();
      formData.append('maid_id', selectedMaidId);
      formData.append('maid_selection_postponed', '0'); // لم يتم تأجيل الاختيار
    }

    console.log('Maid data prepared:', {
      isNewContractTab: isNewContractTab,
      maid_selection_postponed: isNewContractTab ? (maidId ? '0' : '1') : '0',
      maid_name: isNewContractTab ? (maidId ? $('#new_maid_name').val() : 'Postponed') : 'From existing order',
      maid_passport: isNewContractTab ? (maidId ? $('#new_maid_passport').val() : 'Postponed') : 'From existing order',
      maid_religion: isNewContractTab ? (maidId ? $('#new_maid_religion').val() : 'Postponed') : 'From existing order',
      maid_job: isNewContractTab ? (maidId ? $('#new_maid_job').val() : 'Postponed') : 'From existing order',
      maid_nationality: isNewContractTab ? (maidId ? $('#new_maid_nationality').val() : 'Postponed') : 'From existing order',
      maid_salary: isNewContractTab ? (maidId ? $('#new_maid_salary').val() : 'Postponed') : 'From existing order',
      maid_experience_years: isNewContractTab ? (maidId ? $('#new_maid_experience_years').val() : 'Postponed') : 'From existing order',
      maid_age: isNewContractTab ? (maidId ? $('#new_maid_age').val() : 'Postponed') : 'From existing order'
    });

    // أضف بيانات العقد - تأكد من الحصول على القيم من التبويب الصحيح
    var contractDate, msanedNumber, visaNumber, contractDuration, tafwedNumber, visaMiladDate, visaHijryDate;

    if (isNewContractTab) {
      contractDate = $('#tab-new-pane #contract_date').val();
      msanedNumber = $('#tab-new-pane #msaned_number').val();
      visaNumber = $('#tab-new-pane #visa_number').val();
      contractDuration = $('#tab-new-pane #contract_duration').val();
      tafwedNumber = $('#tab-new-pane #tafwed_number').val();
      visaMiladDate = $('#tab-new-pane #visa_milad_date').val();
      visaHijryDate = $('#tab-new-pane #visa_hijry_date').val();
    } else {
      contractDate = $('#tab-order-pane #contract_date').val();
      msanedNumber = $('#tab-order-pane #msaned_number').val();
      visaNumber = $('#tab-order-pane #visa_number').val();
      contractDuration = $('#tab-order-pane #contract_duration').val();
      tafwedNumber = $('#tab-order-pane #tafwed_number').val();
      visaMiladDate = $('#tab-order-pane #visa_milad_date').val();
      visaHijryDate = $('#tab-order-pane #visa_hijry_date').val();
    }

    console.log('Contract data being sent:', {
      contract_date: contractDate,
      msaned_number: msanedNumber,
      visa_number: visaNumber,
      contract_duration: contractDuration
    });

    formData.append('contract_date', contractDate || '');
    formData.append('msaned_number', msanedNumber || '');
    formData.append('visa_number', visaNumber || '');
    formData.append('contract_duration', contractDuration || '2');

    console.log('Contract data prepared:', {
      isNewContractTab: isNewContractTab,
      contract_date: contractDate || '',
      msaned_number: msanedNumber || '',
      visa_number: visaNumber || '',
      contract_duration: contractDuration || '2'
    });

    // أضف الحقول الإضافية للعقد
    formData.append('tafwed_number', tafwedNumber || '');
    formData.append('visa_milad_date', visaMiladDate || '');
    formData.append('visa_hijry_date', visaHijryDate || '');

    // إضافة المسوق
    var marketerId = '';
    if (isNewContractTab) {
      marketerId = $('#tab-new-pane #marketer_id').val();
    } else {
      marketerId = $('#tab-order-pane #marketer_id').val();
    }
    formData.append('marketer_id', marketerId || '');

    // إضافة الجنسية المطلوبة للعاملة (إذا لم يتم اختيار عاملة)
    if ($('#new-no-maid-alert').is(':visible')) {
      formData.append('required_maid_nationality', $('#required_maid_nationality').val() || '');
    }

    console.log('Additional contract data prepared:', {
      isNewContractTab: isNewContractTab,
      tafwed_number: tafwedNumber || '',
      visa_milad_date: visaMiladDate || '',
      visa_hijry_date: visaHijryDate || ''
    });
    // زر الحفظ
    var submitButton = $('#newContractSubmitBtn');
    console.log('Submit button found:', submitButton.length);
    console.log('Submit button current state:', {
      disabled: submitButton.prop('disabled'),
      html: submitButton.html()
    });
    submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');
    var actionUrl = $(this).attr('action');
    console.log('Sending AJAX request to:', actionUrl);
    console.log('Current window location:', window.location.href);
    console.log('Form data entries:');
    for (var pair of formData.entries()) {
      console.log(pair[0] + ': ' + pair[1]);
    }

    console.log('About to send AJAX request...');
    console.log('AJAX settings:', {
      url: $(this).attr('action'),
      type: 'POST',
      processData: false,
      contentType: false
    });

    // إرسال البيانات
    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        console.log('AJAX success response:', response);
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'تم بنجاح',
            text: response.message || 'تم إنشاء العقد بنجاح',
            confirmButtonText: 'حسناً'
          }).then((result) => {
            window.location.href = "{{ route('RecruitmentContract.index') }}";
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: response.message || 'حدث خطأ أثناء إنشاء العقد',
            confirmButtonText: 'حسناً'
          });
        }
      },
      error: function(xhr, status, error) {
        console.log('AJAX error:', {
          status: status,
          error: error,
          responseText: xhr.responseText,
          responseJSON: xhr.responseJSON
        });

        let errorMessage = 'حدث خطأ أثناء إنشاء العقد';
        if (xhr.responseJSON) {
          if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            errorMessage = Object.values(errors).flat().join('\n');
          }
        }
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: errorMessage,
          confirmButtonText: 'حسناً'
        });
      },
      complete: function() {
        console.log('AJAX request completed');
                    submitButton.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>حفظ');
                }
            });
        });
    });
</script>

@include('admin.ingaz.RecruitmentContract.js')
@endsection
