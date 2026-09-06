<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\FinancialPositionService;

class FinancialPositionController extends Controller
{
    public function index(Request $request, FinancialPositionService $service)
    {
        $input = $request->validate([
            'to_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'compare_date' => ['nullable', 'date_format:Y-m-d', 'before:to_date'],
            'level' => ['nullable', 'in:0,1,2,all'],
            'show_zero' => ['nullable', 'boolean'],
        ]);
        $date = $input['to_date'] ?? now()->toDateString();
        return view('finance::finance.financial_position.index', [
            'bladeTitle' => 'قائمة المركز المالي (الميزانية العمومية)',
            'report' => $service->report($date),
            'comparison' => !empty($input['compare_date']) ? $service->report($input['compare_date']) : null,
            'level' => $input['level'] ?? '1',
            'showZero' => $request->boolean('show_zero'),
        ]);
    }
}
