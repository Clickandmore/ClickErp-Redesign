<?php

namespace App\Http\Actions\site;

use App\Http\Actions\MainAction;
use App\Http\Requests\site\RenewContractRequest;
use App\Models\ingaz\User;
use App\Models\ProfessionalApplication;
use App\Models\RenewContract;
use Illuminate\Support\Facades\Auth;

class RenewContractAction extends MainAction
{
    public function __construct(RenewContract $model)
    {
        $this->model = $model;
        $this->fileFolder = 'renew_contracts_files/';
    }

    public function storeRenewContract($data)
    {
        if (Auth::check() && empty($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }
        if (isset($data['sponsor_card'])) {
            $data['sponsor_card'] = uploadFile($data['sponsor_card'], $this->fileFolder);

        }
        if (isset($data['worker_residence'])) {
            $data['worker_residence'] = uploadFile($data['worker_residence'], $this->fileFolder);

        }
        if (isset($data['first_page_of_the_worker_passport'])) {
            $data['first_page_of_the_worker_passport'] = uploadFile($data['first_page_of_the_worker_passport'], $this->fileFolder);

        }
        if (isset($data['travel_tickets'])) {
            $data['travel_tickets'] = uploadFile($data['travel_tickets'], $this->fileFolder);

        }
        if (isset($data['visa_printed_in_the_worker_passport'])) {
            $data['visa_printed_in_the_worker_passport'] = uploadFile($data['visa_printed_in_the_worker_passport'], $this->fileFolder);

        }
        if (isset($data['exit_and_re_entry_visa'])) {
            $data['exit_and_re_entry_visa'] = uploadFile($data['exit_and_re_entry_visa'], $this->fileFolder);

        }
        if (isset($data['customer_national_address'])) {
            $data['customer_national_address'] = uploadFile($data['customer_national_address'], $this->fileFolder);

        }
        if (isset($data['worker_information'])) {
            $data['worker_information'] = uploadFile($data['worker_information'], $this->fileFolder);

        }
        if (isset($data['image_of_bank_transfer'])) {
            $data['image_of_bank_transfer'] = uploadFile($data['image_of_bank_transfer'], $this->fileFolder);
        }
        return $this->store($data);

    }

}
