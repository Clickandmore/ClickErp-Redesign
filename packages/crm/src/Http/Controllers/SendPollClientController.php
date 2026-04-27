<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Models\Client;
use Nami\Crm\Services\ClientService as ObjService;
use Nami\Crm\Http\Requests\ClientRequest as StoreRequest;
use Nami\Crm\Models\ClientPoll;
use Nami\Crm\Models\ClientPollAnswer;
use Nami\Crm\Models\AttachmentCategory;
use Nami\Crm\Models\ClientAttachment;
use Nami\Crm\Models\Poll;
use Illuminate\Http\Request;
use Nami\Crm\Models\Client as ObjModel;
use Yajra\DataTables\DataTables;


class SendPollClientController extends Controller
{
    public $folderPath = "crm::crm.polls";
    public $mainRoute = "crm.polls";

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $clients = ObjModel::where('email', '!=', null)->get();
            $poll_id = $request->poll_id;
            $returnHTML = view($this->folderPath . ".send_poll_client", compact('poll_id', 'clients'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function store(request $request)
    {
        ClientPoll::create([
            'client_id' => $request->client_id,
            'poll_id' => $request->poll_id,
        ]);
        $user = Client::find($request->client_id);
        $pollId = $request->poll_id;
        if ($user->email == null) {
            $url = route('start-poll', ['email' => "no_email", 'poll_id' => $pollId]);
        } else {
            $url = route('start-poll', ['email' => $user->email, 'poll_id' => $pollId]);
        }
        $whatsappUrl = "https://wa.me/{$user->whatsapp}/?text=" . urlencode($url);
        return redirect($whatsappUrl);
    }


    public function show($user_id)
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
            return view( 'crm.clients.polls', compact('polls'));
        }

    }

}
