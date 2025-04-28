<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\UserAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\User as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{

    public $folderPath = "admin.ingaz.clients";
    public $postData = ["is_blocked"];
    public $mainRoute = "clients";

    function __construct()
    {
        // $this->middleware('permission:العملاء-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:العملاء-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:العملاء-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:العملاء-حذف,admin', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $units = objModel::query()->orderBy("id", "ASC");
            //->query()->orderBy("id", "DESC")->get()
            return DataTables::of($units)
                ->addIndexColumn()
                ->editColumn('is_blocked', function ($row) {
                    return
                        $row->is_blocked == 0 ? '
                        <button class="btn btn-sm btn-success update-status" id="update-status" data-id="' . $row->id . '">مفعل</button>' :
                            '<button class="btn btn-sm btn-danger update-status" data-id="' . $row->id . '">محظور </button>';
                })
                ->editColumn('phone', function ($row) {
                    return getPhoneWithTag($row->phone_code.$row->phone);
                })
                ->editColumn('created_at', function ($row) {
                    return ($row->created_at->format('Y-m-d h:i A'));
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '';

                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW)) {
                        $buttons .= '<a href="' . route($this->mainRoute . '.edit', $row->id) . '" class="btn btn-sm btn-primary">' . __('ingaz.edit') . '</a> ';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_DELETE)) {
                        $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $buttons;
                })
                ->escapeColumns([])
                ->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("auth.clients");
        $data["addButtonText"] = __("auth.clients");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $nationalities = \App\Models\ingaz\Nationality::all();
        return view($this->folderPath . '.create', compact('nationalities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, objAction $action)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'nationality_id' => 'nullable|integer',
            'national_num' => 'nullable|string|max:50',
            'birthdate' => 'nullable|date',
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'rooms_num' => 'nullable|integer|min:0',
            'family_num' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'job' => 'nullable|string|max:100'
        ]);

        $data['password'] = bcrypt($data['password']);
        $data['is_blocked'] = 0;

        $client = objModel::create($data);

        return response()->json([
            'code' => 200,
            'message' => __('ingaz.client_created_successfully'),
            'url' => route($this->mainRoute . '.index')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $client = objModel::findOrFail($id);
        return view($this->folderPath . '.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $client = objModel::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'national_num' => 'nullable|string|max:50',
            'birthdate' => 'nullable|date',
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'rooms_num' => 'nullable|integer|min:0',
            'family_num' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'job' => 'nullable|string|max:100',
            'is_blocked' => 'required|boolean'
        ]);

        $client->update($data);

        return redirect()->route($this->mainRoute . '.index')
            ->with('success', __('ingaz.client_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
