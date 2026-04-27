<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ingaz\User as ClientUser;
use Nami\Crm\Services\ClientAttachmentService as ObjService;
use Nami\Crm\Http\Requests\ClientAttachmentRequest as ObjRequest;
use Nami\Crm\Models\AttachmentCategory;
use Illuminate\Http\Request;
use Nami\Crm\Models\ClientAttachment as ObjModel;
use Yajra\DataTables\DataTables;


class ClientAttachmentController extends Controller
{
    public $folderPath = "crm::crm.client_attachments";
    public $mainRoute = "crm.client_attachments";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()
                ->with([
                    'user:id,name',
                    'client:id,name,client_id',
                    'client.client:id,name',
                    'attachment:id,title',
                ])
                ->latest();

            if ($request->filled('user_id')) {
                $userId = (int) $request->input('user_id');
                $dataTable->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('client', function ($crmClientQuery) use ($userId) {
                            $crmClientQuery->where('client_id', $userId);
                        });
                });
            }

            if ($request->filled('name')) {
                $name = $request->input('name');
                $dataTable->where(function ($query) use ($name) {
                    $query->whereHas('user', function ($userQuery) use ($name) {
                        $userQuery->where('name', 'like', '%' . $name . '%');
                    })->orWhereHas('client', function ($crmClientQuery) use ($name) {
                        $crmClientQuery->where('name', 'like', '%' . $name . '%')
                            ->orWhereHas('client', function ($linkedUserQuery) use ($name) {
                                $linkedUserQuery->where('name', 'like', '%' . $name . '%');
                            });
                    });
                });
            }

            return DataTables::of($dataTable->get())
                ->addIndexColumn()
                ->addColumn('client', function ($row) {
                    return $row->client_display_name ?: '-';
                })
                ->addColumn('attachment', function ($row) {
                    return $row->attachment->title ?? '-';
                })
                ->addColumn('file', function ($row) {
                    if (!$row->file) {
                        return '<span class="text-muted">-</span>';
                    }

                    $fileUrl = showFile($row->file);

                    return '<div class="d-flex gap-1">
                                <a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary" title="عرض الملف">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="' . $fileUrl . '" download class="btn btn-sm btn-outline-secondary" title="تنزيل الملف">
                                    <i class="fa fa-download"></i>
                                </a>
                            </div>';
                })
                ->addColumn('actions', function ($row) {
                    $showButton = showButton(route($this->mainRoute . ".show", $row->id), $row->title);
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $showButton . " " . $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }

        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm::crm.client_attachments");
        $data["addButtonText"] = __("crm::crm.client_attachments");

        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $data['attachments'] = AttachmentCategory::get();
            $data['clients'] = ClientUser::query()->select('id', 'name')->orderBy('name')->get();
            $returnHTML = view($this->folderPath . ".create")->with($data)->render();

            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->storeClientAttachment($dataInsert,$request);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request,$id, ObjService $service)
    {
        if ($request->ajax()) {
            $data['obj'] = ObjModel::query()
                ->with([
                    'user:id,name',
                    'client:id,name,client_id',
                    'client.client:id,name',
                    'attachment:id,title',
                ])
                ->findOrFail($id);
            $returnHTML = view($this->folderPath . '.show')->with($data)->render();

            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $data['obj'] = $service->find($id);
            $data['attachments'] = AttachmentCategory::get();
            $data['clients'] = ClientUser::query()->select('id', 'name')->orderBy('name')->get();
            $data['selectedUserId'] = $data['obj']->user_id ?: optional(optional($data['obj']->client)->client)->id;
            $returnHTML = view($this->folderPath . ".edit")->with($data)->render();

            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ObjRequest $request, $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
