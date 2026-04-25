<?php

namespace App\Http\Controllers\admin\office;

use App\Enums\Office\OfficesStatusEnum;
use App\Http\Actions\office\OfficeAction as objAction;
use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;

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
