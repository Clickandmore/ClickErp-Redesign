@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection

@section('page-title')
    {{helperTrans("ingaz.maids")}}
@endsection


@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))

    <div class="container mt-4">
        <div class="row justify-content-center">
            <!-- Image Section -->
            <div class="col-md-12 d-flex justify-content-center mb-4">
                <a href="#" class="text-decoration-none" data-id="{{ @$maid->id }}">
                    @if ($maid->image != null)
                        <img src="{{ showFile($maid->image) }}" alt="cv1" class="img-fluid rounded-circle" style="width: 130px; height: 130px; object-fit: cover;">
                    @else
                        <img src="{{ $maid->cv_preview ? showFile($maid->cv_preview) : showFile($maid->cv_file) }}" alt="cv1" class="img-fluid rounded-circle" style="width: 130px; height: 130px; object-fit: cover;">
                    @endif
                </a>
            </div>

            <!-- Details Section -->
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">اسم العاملة:</span>
                            <h5 class="d-inline">{{ $maid->name }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">مكتب الاستقدام:</span>
                            <h5 class="d-inline">{{ $maid->office->title }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">نوع السيرة الذاتية:</span>
                            <h5 class="d-inline">{{ $maid->jobTypes->first() ? $maid->jobTypes->first()->title : '-' }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">العمر:</span>
                            <h5 class="d-inline">{{ $maid->age }} سنة</h5>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('web.nationality') }}:</span>
                            <h5 class="d-inline">{{ $maid->nationality->sub_title }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('web.salary') }}:</span>
                            <h5 class="d-inline">{{ $maid->salary }} ر.س</h5>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('web.passport_number') }}:</span>
                            <h5 class="d-inline">{{ $maid->passport_num }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('web.experience_years') }}:</span>
                            <h5 class="d-inline">{{ $maid->experience_years }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('web.religion') }}:</span>
                            <h5 class="d-inline">{{ $maid->religion->title }}</h5>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('web.recruitment_price') }}:</span>
                            <h5 class="d-inline">{{ $maid->nationality->price }} ر.س</h5>
                        </div>
                    </div>

                    @if (@$maid->height)
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('ingaz.height') }}:</span>
                            <h5 class="d-inline">{{ @$maid->height }}</h5>
                        </div>
                    </div>
                    @endif
                    @if (@$maid->weight)
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <span class="fw-bold">{{ helperTrans('ingaz.weight') }}:</span>
                            <h5 class="d-inline">{{ @$maid->weight }}</h5>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <hr>
    <h5>@lang('ingaz.orders')</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ __('ingaz.ID') }}</th>
                    <th>{{ __('ingaz.order_code') }}</th>
                    <th>{{ __('ingaz.client') }}</th>
                    <th>{{ __('ingaz.phone') }}</th>
                    <th>{{ __('ingaz.delivery') }}</th>
                    <th>{{ __('ingaz.order_type') }}</th>
                    <th>{{ __('ingaz.status') }}</th>
                    <th>{{ __('ingaz.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($maid->orders as $order)
                    <tr>
                        <td>{{ @$order->id }}</td>
                        <td>{{ @$order->order_code }}</td>
                        <td>{{ @$order->user->name }}</td>
                        <td>
                            <a href="tel:{{ @$order->user->phone }}">{{ @$order->user->phone }}
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16">
                                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"></path>
                                </svg>
                            </a>
                        </td>
                        <td>
                            <span class="badge {{ @$order->delivery ? 'bg-success' : 'bg-danger' }}">{{ @$order->delivery ? 'نعم' : 'لا' }}</span>
                        </td>
                        <td>{{ @$order->jobType->title }}</td>
                        <td>
                            <span class="badge bg-warning">{{ $order->status }}</span>
                        </td>
                        <td>
                            {!! editButton(route('orders.edit', $order->id), __("Edit Order")) !!}
                            {{-- {!! deleteButton(route('orders.destroy', $order->id)) !!} --}}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <hr>
    <h5>@lang('ingaz.contracts')</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ __('ingaz.ID') }}</th>
                    <th>{{ __('ingaz.client') }}</th>
                    <th>{{ __('ingaz.phone') }}</th>
                    <th>{{ __('ingaz.status') }}</th>
                    <th>{{ __('ingaz.remainingDays') }}</th>
                    <th>{{ __('ingaz.contract_date') }}</th>
                    <th>{{ __('ingaz.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($maid->contracts as $contract)
                    <tr>
                        <td>{{ @$contract->id }}</td>
                        <td>{{ @$contract->user->name }}</td>
                        <td>
                            <a href="tel:{{ @$contract->user->phone }}">{{ @$contract->user->phone }}
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16">
                                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"></path>
                                </svg>
                            </a>
                        </td>
                        <td>{{ @$contract->status }}</td>
                        <td>{{ @ahxCalcContractRemaining(@$contract->id) ?? '-' }}</td>
                        <td>{{ @getContractInfo($contract->id) ? getContractInfo($contract->id)->contract_date : '-'  }}</td>
                        <td>
                            <a class="btn btn-primary" href="{{ route('getContractEdit', $contract->id) }}">
                                <i class="fa fa-pencil"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection


@section('js')
<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
@endsection
