<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\OfferResource;
use App\Models\ingaz\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ApiOfferController extends Controller
{
    public function index(Request $request)
    {
        $offers = $this->activeOffersQuery()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return apiResponse(true, __('api.All items'), OfferResource::collection($offers), 200);
    }

    public function show($id)
    {
        $offer = $this->activeOffersQuery()->find($id);

        if (!$offer) {
            return apiResponse(false, __('api.not found'), null, 404);
        }

        return apiResponse(true, __('api.All items'), new OfferResource($offer), 200);
    }

    private function activeOffersQuery()
    {
        $today = Carbon::today();

        return Offer::query()
            ->with([
                'nationalities',
                'maids.nationality',
                'maids.jobTypes',
                'maids.jobTitles',
                'maids.expertises.country',
                'maids.expertises.jobTitle',
            ])
            ->where('is_active', true)
            ->where(function ($query) use ($today) {
                $query->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $today);
            });
    }
}
