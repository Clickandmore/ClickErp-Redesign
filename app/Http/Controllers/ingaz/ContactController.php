<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ContactAction;
use App\Http\Actions\ingaz\ContactAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Contact as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ContactController extends Controller
{

    public $folderPath = "admin.ingaz.contact";
    public $mainRoute = "contact";


    function __construct()
    {
        // $this->middleware('permission:الاتصال-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الاتصال-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الاتصال-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الاتصال-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $contacts = objModel::query()->latest();
            return DataTables::of($contacts)
                ->addIndexColumn()
                ->editColumn('phone', function ($rows) {
                    return getPhoneWithTag($rows->phone);
                })
                ->editColumn('email', function ($rows) {
                    return getEmailWithTag($rows->email);
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        return view($this->folderPath . '.index', $data);
    }

    public function destroy($id, ContactAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
