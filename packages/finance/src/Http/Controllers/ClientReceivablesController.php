<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nami\Finance\Services\ClientReceivablesService;

class ClientReceivablesController extends Controller
{
    public function index(Request $request, ClientReceivablesService $service)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'state' => ['nullable', Rule::in(array_keys(ClientReceivablesService::STATES))],
            'balance' => ['nullable', Rule::in(['due', 'all'])],
            'extended' => ['nullable', Rule::in(['0', '1'])],
            'per_page' => ['nullable', Rule::in([25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
            'branch_id' => ['nullable', 'integer'],
        ]);
        $filters = array_replace(['search' => '', 'state' => 'all', 'balance' => 'due', 'extended' => '', 'per_page' => 25], array_filter($filters, fn ($v) => $v !== null));
        $query = $service->query($filters);
        $totals = DB::query()->fromSub(clone $query, 'report')
            ->selectRaw('COUNT(*) as contracts, COUNT(DISTINCT user_id) as clients, COALESCE(SUM(billed), 0) as billed, COALESCE(SUM(paid), 0) as paid, COALESCE(SUM(adjustments), 0) as adjustments, COALESCE(SUM(GREATEST(remaining, 0)), 0) as due')->first();
        $rows = $query->orderByDesc('remaining')->orderBy('c.id')->paginate((int) $filters['per_page'])->withQueryString();
        $ids = $rows->pluck('id')->all();
        return view('finance::finance.client_receivables.index', [
            'bladeTitle' => 'مستحقات العملاء وعقود الإيجار', 'filters' => $filters, 'totals' => $totals,
            'rows' => $rows, 'documents' => $service->documents($ids), 'extensions' => $service->extensions($ids),
        ]);
    }
}
