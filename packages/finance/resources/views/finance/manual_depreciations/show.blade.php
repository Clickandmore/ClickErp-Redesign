@extends('click.layout.indexs.index')
@section("style")

@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
        <div class="tab-content mt-5">
            <div class="tab-pane fade show active" id="info_tap" role="tabpanel">
                <div class="container">
                    <!-- Basic and Financial Details (Combined Section) -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6>{{ __("finance::finance.Basic and financial details") }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row detail-row">
                                <div class="col-md-4"><strong>{{ __("finance::finance.registration_type") }}:</strong>
                                    <span>{{ __("finance::finance." . $obj->registration_type) }}</span></div>
                                <div class="col-md-4"><strong>{{ __("finance::finance.main_account") }}:</strong>
                                    <span>{{ @$obj->parent_account->name}}</span></div>
                                <div class="col-md-4"><strong>{{ __("finance::finance.code") }}:</strong>
                                    <span>{{ $obj->code }}</span></div>
                            </div>
                            <div class="row detail-row">
                                <div class="col-md-4"><strong>{{ __("finance::finance.name") }}:</strong>
                                    <span>{{ $obj->name }}</span></div>
                                <div class="col-md-4"><strong>{{ __("finance::finance.purchase_value") }}:</strong>
                                    <span>{{ $obj->purchase_value }}</span></div>
                                <div class="col-md-4"><strong>{{ __("finance::finance.principle_value") }}:</strong>
                                    <span>{{ $obj->principle_value }}</span></div>
                            </div>
                            <div class="row detail-row">
                                <div class="col-md-6"><strong>{{ __("finance::finance.responsible_employee") }}:</strong>
                                    <span>{{ $obj->employee_id ? $employees->find($obj->employee_id)->name : 'غير متوفر' }}</span>
                                </div>
                                <div class="col-md-6"><strong>{{ __("finance::finance.creditor_account") }}:</strong>
                                    <span>{{ $obj->creditor_account_id ? $creditor_accounts->find($obj->creditor_account_id)->name : 'غير متوفر' }}</span>
                                </div>
                            </div>
                            <div class="row detail-row">
                                <div class="col-md-6"><strong>{{ __("finance::finance.service_start_date") }}:</strong>
                                    <span>{{ $obj->service_start_date }}</span></div>
                                <div class="col-md-6"><strong>{{ __("finance::finance.hypothetical_years") }}:</strong>
                                    <span>{{ $obj->hypothetical_years }}</span></div>
                            </div>
                            <div class="row detail-row">
                                <div class="col-md-12"><strong>{{ __("finance::finance.description") }}:</strong>
                                    <span>{{ $obj->description }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Depreciation Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h6>{{ __("finance::finance.perished_details") }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row detail-row">
                                <div class="col-md-6">
                                    <strong>{{ __("finance::finance.perished_type") }}:</strong>
                                    <span>{{ __("finance::finance." . $obj->perished_type) }}</span>
                                </div>
                                @if ($obj->perished_type != "without")
                                    <div class="col-md-6">
                                        <strong>{{ __("finance::finance.account_perished_type") }}:</strong>
                                        <span>{{ __("finance::finance." . @$obj->principlePerished->account_type) }}</span>
                                    </div>
                                @endif
                            </div>

                            @if ($obj->perished_type != "without")
                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <strong>{{ __("finance::finance.perished_end_date") }}:</strong>
                                        <span>{{ @$obj->principlePerished->perished_end_date }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{ __("finance::finance.Period in months") }}:</strong>
                                        <span>{{ @$obj->principlePerished->period }}</span>
                                    </div>
                                </div>
                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <strong>{{ __("finance::finance.junk_value") }}:</strong>
                                        <span>{{ @$obj->principlePerished->junk_value }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{ __("finance::finance.perished_account") }}:</strong>
                                        <span>{{ @$obj->principlePerished->perished_account_id ? $perished_accounts->find($obj->principlePerished->perished_account_id)->name : 'غير متوفر' }}</span>
                                    </div>
                                </div>
                                <div class="row detail-row">
                                    <div class="col-md-6">
                                        <strong>{{ __("finance::finance.expense_account") }}:</strong>
                                        <span>{{ @$obj->principlePerished->expense_account_id ? $perished_accounts->find($obj->principlePerished->expense_account_id)->name : 'غير متوفر' }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>


                    <!-- Attachments Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-teal text-white">
                            <h6>{{ __("finance::finance.attachments") }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="attachments-grid">
                                @if ($obj->files->count() > 0)
                                    @foreach ($obj->files as $image)
                                        <div class="attachment-thumbnail">
                                            <img src="{{ showFile($image->file) }}" alt="attachment"
                                                 class="img-thumbnail">
                                        </div>
                                    @endforeach
                                @else
                                    <p> {{ __("finance::finance.There are no attachments.") }} </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="tab-pane fade" id="movements_tap" role="tabpanel">
                <div class="row-title mb-2">
                    <div>
                        <div class="input-group">

                        </div>
                    </div>
                </div>
                <div class="row">

                    <input type="hidden" name="account_id" class="search_keys" id="account_id" value="{{@$obj->account_id}}">
                    <input type="hidden" name="other_account" class="search_keys" id="other_account" value="yes">

                    <div class="col-md-4">
                        <label for="TextInput" class="form-label"> {{__('finance::finance.From Date')}}</label>
                        <input type="date" name="from_date" class="form-control search_keys" id="from_date">
                    </div>

                    <div class="col-md-4">
                        <label for="TextInput" class="form-label"> {{__('finance::finance.To Date')}}</label>
                        <input type="date" name="to_date" value="{{date("Y-m-d",time())}}"
                               class="form-control search_keys" id="from_date">
                    </div>

                    <div class="col-md-2">
                        <label for="TextInput" class="form-label">{{__('finance::finance.Transaction Type')}}</label>
                        <select name="type" id="type" class="form-control search_keys">
                            <option value="both">{{__('finance::finance.All')}}</option>
                            <option value="debtor" >{{__('finance::finance.debtor')}}</option>
                            <option value="creditor" >{{__('finance::finance.creditor')}}</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <br>
                        <button type="button" id="search_button" class="btn btn-sm btn-success float-right">
                            <i class="fa fa-search"></i> {{ __("finance::finance.Search") }}
                        </button>
                    </div>

                </div>
                <div class="row g-3 m-2" id="account_list"></div>
            </div>


            <div class="tab-pane fade" id="ehlak_tap" role="tabpanel">
                <div class="row-title mb-2">
                    <div>
                        <div class="input-group">

                        </div>
                    </div>
                </div>
                <div class="row">

                    <input type="hidden" name="account_id" class="search_keys2" id="account_id" value="{{@$obj->account_id}}">
                    <input type="hidden" name="other_account" class="search_keys2" id="other_account" value="yes">

                    <div class="col-md-4">
                        <label for="TextInput" class="form-label"> {{__('finance::finance.From Date')}}</label>
                        <input type="date" name="from_date" class="form-control search_keys2" id="from_date2">
                    </div>

                    <div class="col-md-4">
                        <label for="TextInput" class="form-label"> {{__('finance::finance.To Date')}}</label>
                        <input type="date" name="to_date" value="{{date("Y-m-d",time())}}"
                               class="form-control search_keys2" id="from_date2">
                    </div>

                    <input type="hidden" value="1" class="search_keys2" name="type_search" id="type_search">


                    <div class="col-md-2">
                        <br>
                        <button type="button" id="search_button2" class="btn btn-sm btn-success float-right">
                            <i class="fa fa-search"></i> {{ __("finance::finance.Search") }}
                        </button>
                    </div>

                </div>
                <div class="row g-3 m-2" id="ehlak_list"></div>
            </div>
        </div>

@endsection
@section('js')
    <script>
        $("#movement-id , #search_button").on("click", function (e) {
            e.preventDefault()
            var data = $(".search_keys").serialize();
            let url = '{{route("finance.account-statement.index")}}';
            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function () {
                    $("#account_list").html('<div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">' +
                        '  <div class="loader" style="margin: auto;"></div>' +
                        '</div>');
                },
                success: function (result) {
                    // console.log(result);
                    $("#account_list").html(result.data.html);
                    if(result.data.count > 0) {
                        $('.print-and-excel-buttons').removeClass('d-none');
                    }else{
                        $('.print-and-excel-buttons').addClass('d-none');
                    }
                },
                error: function (errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        });


        $("#ehlak-id , #search_button2").on("click", function (e) {
            e.preventDefault()
            var data = $(".search_keys2").serialize();
            let url = '{{route("finance.group-depreciation.index")}}';
            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function () {
                    $("#ehlak_list").html('<div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">' +
                        '  <div class="loader" style="margin: auto;"></div>' +
                        '</div>');
                },
                success: function (result) {
                    // console.log(result);
                    $("#ehlak_list").html(result.data.html);

                },
                error: function (errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        $(document).ajaxComplete(function () {
            $('#toExcel').on('click', function () {
                let table = $('#ehlak_list').find('table').get(0);
                if (table) {
                    // let fromDate = $('#from_date').val().replace(/-/g, '');
                    // let toDate = $('#to_date').val().replace(/-/g, '');
                    let filename = `group_depreciation.xlsx`;

                    let wb = XLSX.utils.table_to_book(table, {sheet: "Sheet JS"});
                    XLSX.writeFile(wb, filename);
                } else {
                    $.toast({
                        heading: '{{helperTrans('finance::finance.error')}}',
                        text: '{{helperTrans('finance::finance.No data available to export')}}',
                        icon: 'error',
                        position: 'top-right',
                        stack: false
                    });
                }
            });
        });
    </script>
@endsection



