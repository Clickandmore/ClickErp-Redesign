<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\PriceOfferDetail;
use App\Services\MainService;


class PriceOfferDetailService extends MainService
{

    function __construct()
    {
        $this->model = new PriceOfferDetail();
        $this->fileFolder = "images/admins/offers/";
    }


}
