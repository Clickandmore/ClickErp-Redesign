<?php

namespace App\Http\Controllers\office;

use App\Enums\Office\OfficesStatusEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\office\AdminOfficeAction;
use App\Http\Actions\office\JopTypeOfficeAction;
use App\Http\Actions\office\OfficeAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\office\OfficeRequest;
use App\Models\office\Office as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

//use function App\Http\Controllers\ingaz\__;
//use function App\Http\Controllers\ingaz\response;
//use function App\Http\Controllers\ingaz\route;
//use function App\Http\Controllers\ingaz\view;

class OfficeStatusController extends Controller
{
    public function update(Request $request, $id, objAction $action)
    {
        $obj = $action->find($id);
        if ($obj->status == OfficesStatusEnum::Active->value) {
            $obj->update(['status' => OfficesStatusEnum::DisActive->value]);
        } else {
            $obj->update(['status' => OfficesStatusEnum::Active->value]);
        }
        return jsonSuccess();
    }

}
