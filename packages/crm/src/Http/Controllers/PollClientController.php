<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\ClientService as ObjService;
use Nami\Crm\Http\Requests\ClientRequest as StoreRequest;
use Nami\Crm\Models\ClientPoll;
use Nami\Crm\Models\ClientPollAnswer;
use Nami\Crm\Models\AttachmentCategory;
use Nami\Crm\Models\Client;
use Nami\Crm\Models\ClientAttachment;
use Nami\Crm\Models\Poll;
use Illuminate\Http\Request;
use Nami\Crm\Models\Client as ObjModel;
use Yajra\DataTables\DataTables;


class PollClientController extends Controller
{
    public $folderPath = "crm::crm.poll_clients";
    public $postData = ['name', 'phone', 'whatsapp', 'email', 'notes', 'code', 'social_contact_id', 'city_id', 'area_id', 'status', 'address', 'user_id', 'client_id'];
    public $mainRoute = "crm.poll-clients";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $clients = ClientPoll::where(['poll_id' => $request->poll_id])->pluck('client_id')->toArray();
            $dataTable = Client::query()->whereIn('id', $clients)->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    if ($row->status == 1) {
                        $status = "<button class='btn btn-success update-status' data-id='" . $row->id . "'>" . __("finance.show") . "</button>";
                    } else {
                        $status = "<button class='btn btn-danger update-status' data-id='" . $row->id . "'>" . __("finance.hidden") . "</button>";
                    }
                    return $status;
                })
                ->addColumn('show-sent-polls', function ($row) use ($request) {
                    $count = ClientPoll::where(['poll_id' => $request->poll_id, 'client_id' => $row->id])->count();
                    if ($count)
                        return "<a title='عرض الاستبيانات' class='btn btn-default' href='" . url('crm/sendPoll/'. $row->id) . "' >" . $count . "</a>";
                    else
                        return 0;
                })
                ->editColumn('client_id', function ($row) {
                    if ($row->client_id == null) {
                        $status =
                            '<button type="button" class="editButtonStyle btn btn-warning editButton" data-toggle="modal" data-target="#createModal"
                        title="' . __("purchases.not_confirmed") . '"    model-route="' . route("crm.confirm-clients.edit", $row->id) . '" model-title="' . $row->name . '">
                            <i class="fa fa-check-square"></i>
                            </button>';
                    } else {
                        $status = "<button class='btn btn-success' disabled title='" . __("purchases.is_confirmed") . "'>" . "<i class='fa fa-check-square'></i>" . "</button>";
                    }
                    return $status;
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions', 'status', 'client_id', 'show-sent-polls'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create", ['poll_id' => $request->poll_id]);
        $data["dataTableRoute"] = route($this->mainRoute . ".index", ['poll_id' => $request->poll_id]);
        $data["bladeTitle"] = __("crm.clients");
        $data["addButtonText"] = __("crm.clients");
        $data['polls'] = Poll::latest()->get();
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                "cities" => $service->getCities(),
                "areas" => $service->getAreas(),
                "social_contacts" => $service->getSocialContacts(),
                "users" => $service->getUsers(),
                'attachments' => AttachmentCategory::get(),
                "categories" => $service->getCategories(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(StoreRequest $request, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
        $data = $service->store($dataInsert);
        $data->category()->attach($request->category_id);
        foreach ($request->attachments as $attachment) {
            ClientAttachment::create([
                'client_id' => $data['id'],
                'title' => $attachment['title'],
                'attachment_id' => $attachment['attachment_id'],
                'file' => uploadFile($attachment['file'], "images/admins/client_attachments/"),
            ]);
        }
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, ObjService $service)
    {
        $obj = $service->find($id);
        $obj->status = !$obj->status;
        $obj->save();
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                "cities" => $service->getCities(),
                "areas" => $service->getAreas(),
                "social_contacts" => $service->getSocialContacts(),
                "users" => $service->getUsers(),
                "categories" => $service->getCategories(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRequest $request, $id, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
        $obj = $service->find($id);
        $data = $obj->update($dataInsert);
        $obj->category()->sync($request->category_id);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }

    public function sendPoll(request $request)
    {
        ClientPoll::create([
            'client_id' => $request->user_id,
            'poll_id' => $request->poll_id,
        ]);
        $user = Client::find($request->user_id);
        $pollId = $request->poll_id;
        $url = route('start-poll', ['email' => $user->email, 'poll_id' => $pollId]);
        $whatsappUrl = "https://wa.me/{$user->phone}/?text=" . urlencode($url);
        return redirect($whatsappUrl);
    }

    public function showSentPolls($user_id)
    {
        $count = ClientPoll::where('client_id', $user_id)->count();
        if ($count == 0) {
            return "لا يوجد استبيانات تم ارسالها لهذا العميل";
        } else {
            $polls = ClientPoll::with('poll')
                ->where('client_id', $user_id)
                ->latest()
                ->get();

            foreach ($polls as $row) {
                $row['answers'] = ClientPollAnswer::where('poll_id', $row->poll->id)
                    ->where('client_id', $user_id)
                    ->with('question', 'answer')
                    ->latest()
                    ->get();
            }
            return view($this->folderPath . '.polls', compact('polls'));
        }

    }
}
