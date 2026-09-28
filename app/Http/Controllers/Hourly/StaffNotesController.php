<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyVisit;
use App\Services\Hourly\OperationalNotes;

class StaffNotesController extends Controller
{
    public function driver(HourlyVisit $visit,OperationalNotes $notes){return response()->json(['data'=>$notes->forStaff($visit,'driver',auth('hourly_driver_api')->id())]);}
    public function worker(HourlyVisit $visit,OperationalNotes $notes){return response()->json(['data'=>$notes->forStaff($visit,'worker',auth('hourly_maid_api')->id())]);}
}
