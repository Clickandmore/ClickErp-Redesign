<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\Client;
use Nami\Crm\Models\FollowClient;
use Nami\Crm\Models\PriceOffer;
use App\Services\MainService;


class HomeService extends MainService
{



    public function indexCRM()
    {
        $data['potential_clients'] =  Client::count();

        $data['today_follow_clients'] = FollowClient::where('date',date('Y-m-d'))->get();
        $data['follow_clients'] = FollowClient::count();
        $data['price_offers'] = PriceOffer::count();
        $date1 = FollowClient::pluck('date')->toArray();
        $date2 = PriceOffer::pluck('date')->toArray();
        $all_dates = array_unique(array_merge($date1, $date2));

        $dates = array_count_values($all_dates);
        ksort($dates);
        $dates = array_slice($dates, -12, 12);
        $data['dates'] = $dates;
        $data['new_employees_counts'] = 0;

        return $data;
    }

}
