<?php

namespace Nami\Finance\Services;

use App\Models\ClientAttachment;
use Nami\Finance\MainService;
use Nami\Finance\Models\Client;
use Nami\Finance\Models\ClientImage;

class ClientImageService extends MainService
{
    public function __construct(ClientImage $model)
    {
        $this->model = $model;
        $this->fileFolder = 'ClientImages/';
        $this->files = ['image'];
    }

    public function storeImages($client_id, $data)
    {
        foreach ($data as $key => $value) {
            $this->store([
                'client_id' => $client_id,
                'image' => $value,
            ]);
        }
        return true;
    }
    public function deleteFiles($client_id)
    {
        $files = $this->getWhere(['client_id' => $client_id]);
        if ($files) {
            foreach ($files as $file) {
                deleteFile($file->image);
                $file->delete();
            }
        }
    }
}
