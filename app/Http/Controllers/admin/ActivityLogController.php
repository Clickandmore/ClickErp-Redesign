<?php

namespace App\Http\Controllers\Admin;

use App\Keys;
use App\Constants;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(){
        $data = Activity::orderBy(Keys::id,Keys::desc)->paginate(15);
        return view('admin.activity_log.index',compact('data'));
    }

}
