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


class SendPollUserController extends Controller
{
    public $folderPath = "crm::crm.polls";
    public $mainRoute = "crm.polls";


    public function sendPollUser(Request $request)
    {
        if ($request->ajax()) {
            $users = User::where('email', '!=', null)->whereIn('user_type', ['P', 'C', 'company'])->get();
            $poll_id = $request->poll_id;
            $returnHTML = view($this->folderPath . ".send_poll_user", compact('poll_id', 'users'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }



}
