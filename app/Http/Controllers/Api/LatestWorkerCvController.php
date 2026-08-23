<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Maid;
use Illuminate\Http\Request;

class LatestWorkerCvController extends Controller
{
    public function index(Request $request)
    {
        $type = (string) $request->query('type', '');
        $jobTypeId = match ($type) {
            'estqdam' => 1,
            'egar' => 2,
            default => null,
        };

        if (!$jobTypeId) {
            return apiResponse(true, __('api.All items'), [], 200)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $maids = Maid::shown()
            // السيرة المحجوزة بطلب قائم لا تظهر ضمن أحدث السير على الموقع الخارجي.
            ->withoutActiveOrders()
            ->with([
                'jobTypes',
                'jobTitles',
                'nationality',
                'religion',
                'expertises.country',
                'expertises.jobTitle',
            ])
            ->whereHas('jobTypes', function ($query) use ($jobTypeId) {
                $query->where('job_type_id', $jobTypeId);
            })
            ->orderByDesc('id')
            ->limit(9)
            ->get();

        return apiResponse(true, __('api.All items'), $maids, 200)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
