<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'balance' => ['nullable', Rule::in(array_keys(ClientReceivablesService::BALANCES))],
            'extended' => ['nullable', Rule::in(['0', '1'])],
            'per_page' => ['nullable', Rule::in([25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
            'branch_id' => ['nullable', 'integer'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(array_keys(ClientReceivablesService::SORTS))],
        ]);
        $filters = array_replace(
            ['search' => '', 'state' => 'all', 'balance' => 'due', 'extended' => '', 'per_page' => 25, 'to' => now()->toDateString(), 'sort' => 'latest'],
            array_filter($filters, fn ($v) => $v !== null)
        );

        $all = $service->rows($filters, $filters['to']);
        $totals = $service->totals($all, $filters['to']);
        // الترقيم على العملاء: كل عميل يظهر بكل عقوده المطابقة في صفحة واحدة.
        $clients = $service->clients($all, $filters['to']);
        $perPage = (int) $filters['per_page'];
        $page = max(1, min((int) $request->query('page', 1), max(1, (int) ceil($clients->count() / $perPage))));
        $rows = (new LengthAwarePaginator($clients->forPage($page, $perPage)->values(), $clients->count(), $perPage, $page, [
            'path' => $request->url(),
        ]))->withQueryString();
        $ids = $rows->flatMap(fn ($client) => $client->contracts->pluck('id'))->all();

        return view('finance::finance.client_receivables.index', [
            'bladeTitle' => 'المستحق على عملاء التأجير', 'filters' => $filters, 'totals' => $totals,
            'rows' => $rows, 'documents' => $service->documents($ids, $filters['to']),
            'movements' => $service->movements($ids, $filters['to']), 'extensions' => $service->extensions($ids),
        ]);
    }
}
