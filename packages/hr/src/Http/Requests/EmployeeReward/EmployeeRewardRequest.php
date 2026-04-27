<?php

namespace Nami\Hr\Http\Requests\EmployeeReward;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeRewardRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => 'required|exists:hr_employee,id',
            'reward_id' => 'required|exists:hr_rewards,id',
            'salary' => 'required|numeric',
            'exchange_timing' => 'required|string',
            'date' => 'required',
            'value' => 'required|numeric',
            'net_value' => 'required|numeric',
        ];
    }
}
