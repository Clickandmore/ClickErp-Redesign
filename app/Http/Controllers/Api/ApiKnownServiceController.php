<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\Api\KnownServiceRequestAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\ingaz\SocialStatusAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\KnownServiceRequestStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiKnownServiceController extends Controller
{
    public function index(
        NationalityAction $nationalityAction,
        ReligionAction $religionAction,
        SocialStatusAction $socialStatusAction
    ) {
        return apiResponse(true, __('api.All items'), [
            'nationalities' => $nationalityAction->get(),
            'religions' => $religionAction->getReligions(),
            'socialStatuses' => $socialStatusAction->get(),
        ], 200);
    }

    public function store(KnownServiceRequestStoreRequest $request, KnownServiceRequestAction $knownServiceRequestAction)
    {
        $data = $request->validated();
        unset($data['g-recaptcha-response']);

        if (Auth::guard('api')->check()) {
            $data['user_id'] = Auth::guard('api')->id();
        }

        $result = $knownServiceRequestAction->storeKnownServiceRequest($data);

        return apiResponse(true, 'تم إرسال طلب خدمة معروفة بنجاح', [
            'knownServiceRequest' => $result,
        ], 200);
    }

    public function show($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    public function edit($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    public function update(Request $request, $id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    public function destroy($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }
}
