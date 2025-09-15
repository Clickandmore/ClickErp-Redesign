<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\ingaz\Complaint;

class SidebarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $unreadComplaintsCount = 0; // سيتم تحديثه لاحقاً بعد إضافة العمود
        View::share('unreadComplaintsCount', $unreadComplaintsCount);
    }
}
