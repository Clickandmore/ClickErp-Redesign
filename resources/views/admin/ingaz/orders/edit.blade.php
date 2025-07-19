@if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CONTROL))

    <form class="row g-3 from-submit-global" method="post" action="{{route("orders.update", $obj->id)}}">
        @csrf
        @method('Put')

        <!-- Order Information -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">{{helperTrans('ingaz.order_code')}}</label>
                <input type="text" disabled class="form-control" value="{{ $obj->order_code}}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">{{helperTrans('ingaz.client')}}</label>
                <input type="text" disabled class="form-control" value="{{ $obj->user->name ?? 'غير محدد'}}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">{{helperTrans('ingaz.status')}}</label>
                <input type="text" disabled class="form-control" value="{{getEnumValue('orders_statuses', $obj->status)}}">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">{{helperTrans('ingaz.order created at')}}</label>
                <input type="text" disabled class="form-control" value="{{$obj->created_at->format('Y-m-d h:i A')}}">
            </div>
        </div>

        @if($obj->maid && isset($obj->maid->available_at) && now() < $obj->maid->available_at)
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                يرجي العلم ان تلك العاملة متاحة من يوم {{$obj->maid->available_at}}
            </div>
        @endif

        @if(in_array($obj->status, ['refused', 'done', 'completed']))
            <!-- Disabled for refused, done, or completed orders -->
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                لا يمكن تعديل هذا الطلب لأنه {{getEnumValue('orders_statuses', $obj->status)}}
            </div>

            <!-- Close button only for disabled orders -->
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> {{__("buttons.close")}}
                    </button>
                </div>
            </div>
        @else
            <!-- Status Update Section -->
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fa fa-edit"></i> {{helperTrans('ingaz.update order status')}}</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @php
                            $currentStatus = $obj->status;
                            $availableStatuses = [];

                            // Define available statuses based on current status
                            switch($currentStatus) {
                                case 'new':
                                    $availableStatuses = ['under_process', 'refused'];
                                    break;
                                case 'under_process':
                                    $availableStatuses = ['contracted', 'contracted_without_contract', 'refused'];
                                    break;
                                case 'contracted':
                                    $availableStatuses = ['completed', 'done'];
                                    break;
                                case 'contracted_without_contract':
                                    $availableStatuses = ['completed', 'done'];
                                    break;
                                case 'completed':
                                    $availableStatuses = ['done'];
                                    break;
                                default:
                                    // Show all statuses for other cases
                                    $availableStatuses = array_map(function($case) {
                                        return $case->value;
                                    }, \App\Enums\ingaz\OrderEnum::cases());
                                    break;
                            }
                        @endphp

                        @foreach($availableStatuses as $status)
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" value="{{ $status }}" id="{{ $status }}">
                                    <label class="form-check-label" for="{{ $status }}">
                                        {{ getEnumValue('orders_statuses', $status) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-save"></i> {{__("buttons.save")}}
                    </button>

                    @if($obj->status == 'under_process')
                        @if (@AdminGuardHasPerm(\App\PermKeys::FULL_CONTRACT))
                            <button type="button" class="btn btn-success addNewContract" data-id="{{ $obj->id }}">
                                <i class="fa fa-plus"></i> {{__("admin.add_new_contract")}}
                            </button>
                        @endif
                    @endif

                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> {{__("buttons.close")}}
                    </button>
                </div>
            </div>
        @endif
    </form>

@else
    @include('components.no_permissions')
@endif
