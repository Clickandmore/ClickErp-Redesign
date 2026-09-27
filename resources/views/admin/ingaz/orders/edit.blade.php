@if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CHANGE_STATUS))

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
                <input type="text" disabled class="form-control" value="{{ $obj->user->name ?? helperTrans('ingaz.not_specified') }}">
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
                {{ __('ingaz.maid_available_from_date', ['date' => $obj->maid->available_at]) }}
            </div>
        @endif

        @php
            $hasContract = $obj->relationLoaded('contracts')
                ? $obj->contracts->isNotEmpty()
                : $obj->contracts()->exists();
            $canAddContract = !$hasContract && in_array($obj->status, [
                \App\Enums\ingaz\OrderEnum::UnderProcess->value,
                \App\Enums\ingaz\OrderEnum::Contracted->value,
                \App\Enums\ingaz\OrderEnum::ContractedWithoutContract->value,
                \App\Enums\ingaz\OrderEnum::Completed->value,
                \App\Enums\ingaz\OrderEnum::Done->value,
            ], true);
        @endphp

        @if(in_array($obj->status, ['refused', 'done', 'completed']))
            <!-- Disabled for refused, done, or completed orders -->
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                {{ __('ingaz.order_cannot_be_edited_status', ['status' => getEnumValue('orders_statuses', $obj->status)]) }}
            </div>

            <!-- Close button only for disabled orders -->
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> {{__("buttons.close")}}
                    </button>

                    @if($canAddContract)
                        @if (@AdminGuardHasPerm(\App\PermKeys::FULL_CONTRACT))
                            <button type="button" class="btn btn-success addNewContract" data-id="{{ $obj->id }}">
                                <i class="fa fa-plus"></i> {{__("admin.add_new_contract")}}
                            </button>
                        @endif
                    @endif
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
                                    $availableStatuses = ['refused'];
                                    break;
                                case 'contracted':
                                    $availableStatuses = $hasContract ? ['completed', 'done'] : ['refused'];
                                    break;
                                case 'contracted_without_contract':
                                    $availableStatuses = $hasContract ? ['completed', 'done'] : ['refused'];
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
                            @continue(in_array($status, ['refused', 'return'], true) && !$obj->adminCanReject(auth('admin')->user()))
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

            <div class="mt-3 d-none" data-refuse-reason>
                <label class="form-label fw-bold" for="refuse_reason">{{ helperTrans('ingaz.reason') }}</label>
                <textarea name="refuse_reason" id="refuse_reason" rows="3" class="form-control"
                          placeholder="{{ helperTrans('ingaz.enter_reason') }}">{{ old('refuse_reason', $obj->refuse_reason) }}</textarea>
                <small class="text-muted">{{ helperTrans('ingaz.refuse_reason_optional') }}</small>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-save"></i> {{__("buttons.save")}}
                    </button>

                    @if($canAddContract)
                        @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_ADD_CONTRACT))
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
