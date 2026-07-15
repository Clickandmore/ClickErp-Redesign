<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\FiscalYearClosingRequest;
use Nami\Finance\Services\FiscalYearClosingService;

class FiscalYearClosingController extends Controller
{
    public function index(Request $request, FiscalYearClosingService $service)
    {
        $data = $service->pageData(
            $request->filled('year_id') ? (int) $request->input('year_id') : null,
            $request->input('closing_date')
        );
        $data['bladeTitle'] = __('finance::finance.Closing the fiscal year');

        return view('finance::finance.fiscal_year_closing.index', $data);
    }

    public function store(FiscalYearClosingRequest $request, FiscalYearClosingService $service)
    {
        $validated = $request->validated();
        $constraint = $service->close(
            (int) $validated['year_id'],
            $validated['closing_date'],
            $validated['allocations'],
            $validated['description'] ?? null
        );

        return redirect()
            ->route('finance.fiscal-year-closing.index', ['year_id' => $constraint->year_id])
            ->with('success', 'تم إنشاء قيد الإقفال المالي رقم ' . ($constraint->code ?? $constraint->id) . ' بنجاح.');
    }

    public function reverse(int $operation, FiscalYearClosingService $service)
    {
        $closure = $service->reverse($operation);

        return redirect()
            ->route('finance.fiscal-year-closing.index')
            ->with('success', 'تم التراجع عن عملية الإقفال رقم ' . ($closure->operation_number ?? $closure->id) . ' بنجاح.');
    }
}
