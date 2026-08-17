<div class="row g-2">
    <div class="col-md-12 col-12 mb-2">
        <label for="TextInput" class="form-label">{{ __("hr::hr.salary") }} </label>
        <input type="text" name="salary" id="salary" value="{{ $obj->salary }}" class="form-control" data-validation="required" oninput="updateNetSalary()">
    </div>

    <div class="col-12">
        <div class="row">
            <div class="col-md-6">
                <table id="taxTable" class="table table-bordered">
                    <thead>
                    <tr>
                        <th scope="col">{{ __("hr::hr.Select") }}</th>
                        <th scope="col">{{ __("hr::hr.Choose your tax bracket") }}</th>
                        <th scope="col">{{ __("hr::hr.percentage") }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($taxs as $tax)
                        <tr>
                            <td>
                                <input type="checkbox" class="tax" name="tax[]" value="{{ $tax->id }}"
                                    {{ isset($obj) && $obj->hasTax($tax->id) ? 'checked' : '' }}>
                            </td>
                            <td>{{ $tax->title }}</td>
                            <td>
                                <div class="d-flex">
                                    <input type="text" name="taxValue[]"  class="form-control" value="{{ $tax->value }}" readonly>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <table id="insuranceTable" class="table table-bordered">
                    <thead>
                    <tr>
                        <th scope="col">{{ __("hr::hr.Select") }}</th>
                        <th scope="col">{{ __("hr::hr.Choose the insurance category") }}</th>
                        <th scope="col">{{ __("hr::hr.value") }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($insurances as $insurance)
                        <tr>
                            <td>
                                <input type="checkbox" class="insurance" data-type="{{ $insurance->type }}" name="insurances[]" value="{{ $insurance->id }}"
                                    {{ isset($obj) && $obj->hasInsurance($insurance->id) ? 'checked' : '' }}>
                            </td>
                            <td>{{ $insurance->title }}</td>
                            <td>
                                <div class="d-flex">
                                    <input type="hidden" name="insuranceType[{{ $insurance->id }}]" value="{{ $insurance->type }}">
                                    <label>{{ $insurance->type == 0 ? __("hr::hr.percentage") : __("hr::hr.value") }} </label>
                                    @php
                                        $selectedInsurance = $obj->manyInsurances()->where('insurances_id', $insurance->id)->first();
                                    @endphp

                                    <input type="text" name="insuranceValue[{{ $insurance->id }}]" class="form-control"
                                           value="{{ $selectedInsurance ? $selectedInsurance->value : $insurance->emp_value }}">
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="col-md-12">
                <table id="allowanceTable" class="table table-bordered">
                    <thead>
                    <tr>
                        <th scope="col">{{ __("hr::hr.Select") }}</th>
                        <th scope="col">{{ __("hr::hr.Choose the Allowance") }}</th>
                        <th scope="col">{{ __("hr::hr.value") }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($allowances as $allowance)
                        <tr>
                            <td>
                                <input type="checkbox" class="allowances" data-type="{{ $allowance->type }}" name="allowances[]" value="{{ $allowance->id }}"
                                    {{ isset($obj) && $obj->hasAllowance($allowance->id) ? 'checked' : '' }}>
                            </td>
                            <td>{{ $allowance->title }}</td>
                            <td>
                                <div class="d-flex">
                                    <label>
                                        @if ($allowance->type == 1)
                                            {{ __("hr::hr.day") }}
                                        @elseif ($allowance->type == 2)
                                            {{ __("hr::hr.value") }}
                                        @else
                                            {{ __("hr::hr.percentage") }}
                                        @endif
                                    </label>

                                    @php
                                        $selectedAllowance = $obj->manyAllowances()->where('allowances_id', $allowance->id)->first();
                                    @endphp

                                    <input type="hidden" name="typeInput[{{ $allowance->id }}]" value="{{ $allowance->type }}">
                                    <input type="text" name="valueInput[{{ $allowance->id }}]" class="form-control valueInput"
                                           value="{{ $selectedAllowance ? $selectedAllowance->value : $allowance->value }}">
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-12 mb-2">
        <label for="totalTax" class="form-label">{{ __("hr::hr.totalTax") }}</label>
        <input type="text" id="totalTax" name="total_tax" class="form-control" readonly>
    </div>

    <div class="col-md-3 col-12 mb-2">
        <label for="totalInsurances" class="form-label">{{ __("hr::hr.totalInsurances") }}</label>
        <input type="text" id="totalInsurances" name="total_insurances" class="form-control" readonly>
    </div>

    <div class="col-md-3 col-12 mb-2">
        <label for="totalAllowances" class="form-label">{{ __("hr::hr.totalAllowances") }}</label>
        <input type="text" id="totalAllowances" name="total_allowances" class="form-control" readonly>
    </div>

    <div class="col-md-3 col-12 mb-2">
        <label for="netSalary" class="form-label">{{ __("hr::hr.netSalary") }}</label>
        <input type="text" id="netSalary" name="net_salary" value="{{$obj->net_salary}}" class="form-control" readonly>
    </div>
</div>
