<?php

namespace Nami\Finance\Http\Controllers;

use Nami\Finance\Http\Requests\ClientRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Finance\Http\MainController;
use Nami\Finance\Services\ClientService as ObjService;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class ClientController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.clients";
        $this->mainRoute = "finance.clients";
        $this->action = $action;
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $dataTable = $action->getFinancialClientsDataTable($request->only([
                'category_id',
                'account_link',
                'client_status',
                'balance_state',
            ]));

            return DataTables::of($dataTable)
                ->addColumn('client', function ($row) {
                    $type = $row->client_type === 'company' ? 'شركة' : 'فرد';
                    $category = $row->category_name ?: 'غير مصنف';

                    return '<div class="finance-client-name">
                                <strong>' . e($row->name) . '</strong>
                                <span>#' . (int) $row->id . ' · ' . e($type) . ' · ' . e($category) . '</span>
                            </div>';
                })
                ->addColumn('contact', function ($row) {
                    $phones = collect([$row->phone, $row->phone2])->filter()->map(function ($phone) {
                        return '<a href="tel:' . e($phone) . '">' . e($phone) . '</a>';
                    })->implode('<br>');
                    $email = $row->email
                        ? '<a href="mailto:' . e($row->email) . '">' . e($row->email) . '</a>'
                        : '';

                    return $phones . ($phones && $email ? '<br>' : '') . $email ?: '<span class="text-muted">—</span>';
                })
                ->addColumn('document', function ($row) {
                    $isCompany = $row->client_type === 'company';
                    $number = $isCompany ? $row->tax_number : $row->national_num;
                    $label = $isCompany ? 'الرقم الضريبي' : 'رقم الهوية';

                    return $number
                        ? '<span class="finance-document"><small>' . e($label) . '</small>' . e($number) . '</span>'
                        : '<span class="text-muted">—</span>';
                })
                ->addColumn('account', function ($row) {
                    if (!$row->account_id || !$row->account_code) {
                        return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">غير مرتبط بالشجرة</span>';
                    }

                    $statementUrl = route('finance.account-statement-clients.index', [
                        'account_id' => $row->account_id,
                        'other_account' => 'yes',
                    ]);

                    return '<a class="finance-account-link" href="' . e($statementUrl) . '" target="_blank" rel="noopener">
                                <strong>' . e($row->account_code) . '</strong>
                                <span>' . e($row->account_name ?: $row->name) . '</span>
                            </a>';
                })
                ->editColumn('total_debtor', function ($row) {
                    return number_format((float) $row->total_debtor, 2);
                })
                ->editColumn('total_creditor', function ($row) {
                    return number_format((float) $row->total_creditor, 2);
                })
                ->addColumn('movement', function ($row) {
                    $lastMovement = $row->last_movement_at
                        ? Carbon::parse($row->last_movement_at)->format('Y-m-d')
                        : 'لا توجد حركة';

                    return '<div class="finance-movement">
                                <strong>' . number_format((int) $row->movement_count) . ' حركة</strong>
                                <span>آخر حركة: ' . e($lastMovement) . '</span>
                            </div>';
                })
                ->editColumn('created_at', function ($row) {
                    if (!$row->created_at) {
                        return '<span class="text-muted">—</span>';
                    }

                    $createdAt = Carbon::parse($row->created_at);

                    return '<div class="finance-created-at">
                                <strong>' . e($createdAt->format('Y-m-d')) . '</strong>
                                <span>' . e($createdAt->diffForHumans()) . '</span>
                            </div>';
                })
                ->editColumn('status', function ($row) {
                    return $row->is_blocked
                        ? '<span class="badge bg-danger">موقوف</span>'
                        : '<span class="badge bg-success">نشط</span>';
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '<a href="' . e(route('clients.show', $row->id)) . '"
                                    class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"
                                    title="ملف العميل"><i class="fa fa-user"></i></a>';

                    if ($row->account_id && $row->account_code) {
                        $statementUrl = route('finance.account-statement-clients.index', [
                            'account_id' => $row->account_id,
                            'other_account' => 'yes',
                        ]);
                        $buttons .= ' <a href="' . e($statementUrl) . '"
                                        class="btn btn-sm btn-primary" target="_blank" rel="noopener"
                                        title="كشف الحساب"><i class="fa fa-file-text-o"></i></a>';
                    }

                    return $buttons;
                })->escapeColumns([])->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.clients");
        $data["summary"] = $action->getFinancialClientsSummary();
        $data["categories"] = \Nami\Finance\Models\ClientCategory::query()->orderBy('name')->get(['id', 'name']);
        $data["makeAccountsRoute"] = route('make-accounts-for-clients.store');

        return view($this->folderPath . '.index', $data);
    }


    public function show(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = $this->action->find($id);
            $obj->status = !$obj->status;
            $obj->save();
            return jsonSuccess(['success' => true]);
        }
    }

    public function store()
    {
        $dataInsert = $this->request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $this->action->customStore($dataInsert);
        return jsonSuccess($data);
    }

    public function update( $id)
    {
        $dataInsert = $this->request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $this->action->customUpdate($id, $dataInsert);
        return jsonSuccess($data);
    }

    public function destroy($id)
    {
        $this->action->deleteClient($id);
        return jsonSuccess();
    }
    public function showClient(Request $request,$id)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $this->action->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

}
