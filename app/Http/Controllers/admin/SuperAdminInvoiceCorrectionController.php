<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Invoice;
use App\Services\SuperAdminInvoiceCorrectionService;
use Illuminate\Http\Request;

class SuperAdminInvoiceCorrectionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search_by' => ['nullable', 'in:invoice,contract,delegation'], 'number' => ['nullable', 'integer', 'min:1']]);
        $results = collect();
        if ($request->filled('number')) {
            $query = Invoice::withoutGlobalScopes();
            if ($request->input('search_by', 'invoice') === 'contract') {
                $query->where('contract_id', (int) $request->input('number'));
            } elseif ($request->input('search_by') === 'delegation') {
                $query->where('ingaz_delegation_id', (int) $request->input('number'));
            } else {
                $query->where(fn ($q) => $q->where('id', (int) $request->input('number'))->orWhere('legacy_invoice_number', (int) $request->input('number')));
            }
            $results = $query->orderByDesc('id')->limit(100)->get();
        }
        return view('admin.superadmin.invoice-correction', ['results' => $results, 'invoice' => null, 'plan' => null]);
    }

    public function show(int $invoice, SuperAdminInvoiceCorrectionService $service)
    {
        return view('admin.superadmin.invoice-correction', ['results' => collect(), 'invoice' => $service->invoice($invoice), 'plan' => null]);
    }

    public function preview(Request $request, int $invoice, SuperAdminInvoiceCorrectionService $service)
    {
        $plan = $service->preview($invoice, $this->validated($request));
        return view('admin.superadmin.invoice-correction', ['results' => collect(), 'invoice' => $plan['invoice'], 'plan' => $plan]);
    }

    public function update(Request $request, int $invoice, SuperAdminInvoiceCorrectionService $service)
    {
        $data = $this->validated($request);
        $request->validate(['preview_token' => ['required', 'string', 'size:64']]);
        $service->apply($invoice, $data, $request->input('preview_token'));
        return redirect()->route('dev-superadmin.invoice-corrections.show', $invoice)->with('success', 'تم حفظ الفاتورة وتحديث القيود المتأثرة بنجاح.');
    }

    private function validated(Request $request): array
    {
        $rules = ['invoice_date' => ['required', 'date_format:Y-m-d'], 'discount' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/']];
        foreach (SuperAdminInvoiceCorrectionService::LINES as $line) {
            $rules[$line['cost']] = ['required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'];
            $rules[$line['count']] = ['required', 'integer', 'min:0', 'max:10000'];
        }
        foreach (['visa_recruited_count', 'insured_count'] as $field) $rules[$field] = ['sometimes', 'required', 'integer', 'min:0', 'max:10000'];
        $data = $request->validate($rules);
        foreach (['visa_recruited_count', 'insured_count'] as $field) if (isset($data[$field])) $data[$field] = (int) $data[$field];
        foreach (SuperAdminInvoiceCorrectionService::LINES as $line) {
            $data[$line['cost']] = number_format((float) $data[$line['cost']], 2, '.', '');
            $data[$line['count']] = (int) $data[$line['count']];
        }
        $data['discount'] = number_format((float) $data['discount'], 2, '.', '');
        return $data;
    }
}
