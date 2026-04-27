<?php

namespace Nami\Crm\Services;

use App\Services\MainService;
use App\Models\Nami\Admin;
use App\Models\ingaz\User as ClientUser;
use Nami\Crm\Models\Client;
use Nami\Crm\Models\ClientAttachment;


class ClientAttachmentService extends MainService
{

    function __construct()
    {
        $this->model = new ClientAttachment();
        $this->fileFolder = "images/admins/client_attachments/";
    }

    public function storeClientAttachment($data, $request)
    {
        $data = $this->prepareClientAttachmentData($data);

        if ($request->hasFile('file')) {
            $image = $data['file'];
            $data['file'] = uploadFile($image, $this->fileFolder);
        }

        return $this->store($data);
    }

    public function update($id, $data)
    {
        $data = $this->prepareClientAttachmentData($data);

        if (isset($data['file'])) {
            $image = $data['file'];
            $data['file'] = uploadFile($image, $this->fileFolder);
        }

        return parent::update($id, $data);
    }

    protected function prepareClientAttachmentData(array $data): array
    {
        if (!empty($data['user_id'])) {
            $data['client_id'] = $this->resolveCrmClientIdFromUser((int) $data['user_id']);
        }

        return $data;
    }

    protected function resolveCrmClientIdFromUser(int $userId): int
    {
        $user = ClientUser::query()->findOrFail($userId);

        $crmClient = Client::query()->firstOrCreate(
            ['client_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => $user->phone,
                'whatsapp' => $user->phone ?: '#',
                'vat_number' => null,
                'email' => $user->email,
                'notes' => null,
                'code' => null,
                'social_contact_id' => null,
                'city_id' => null,
                'area_id' => null,
                'status' => 1,
                'address' => $user->address,
                'admin_id' => $this->resolveAdminId(),
            ]
        );

        $crmClient->fill([
            'name' => $user->name,
            'phone' => $user->phone,
            'whatsapp' => $user->phone ?: '#',
            'email' => $user->email,
            'address' => $user->address,
        ]);

        if (!$crmClient->admin_id) {
            $crmClient->admin_id = $this->resolveAdminId();
        }

        if ($crmClient->isDirty()) {
            $crmClient->save();
        }

        return $crmClient->id;
    }

    protected function resolveAdminId(): int
    {
        return (int) (auth('admin')->id() ?? Admin::query()->value('id') ?? 1);
    }
}
