<?php

namespace App\Http\Actions\Api;

use App\Http\Actions\MainAction;
use App\Models\KnownServiceRequest;
use Illuminate\Support\Facades\Auth;

class KnownServiceRequestAction extends MainAction
{
    public function __construct(KnownServiceRequest $model)
    {
        $this->model = $model;
        $this->fileFolder = 'known_service_requests/';
    }

    public function storeKnownServiceRequest(array $data)
    {
        if (Auth::guard('api')->check() && empty($data['user_id'])) {
            $data['user_id'] = Auth::guard('api')->id();
        }

        foreach (['worker_passport_copy', 'worker_personal_photo', 'client_identity_copy'] as $fileField) {
            if (isset($data[$fileField])) {
                $data[$fileField] = uploadFile($data[$fileField], $this->fileFolder);
            }
        }

        return $this->store($data);
    }
}
