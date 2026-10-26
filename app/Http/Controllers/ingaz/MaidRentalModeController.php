<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Maid;
use App\Services\Rental\RentalMaidQuery;
use App\Services\Rental\RentalModeSwitcher;
use Illuminate\Http\JsonResponse;

/** أزرار التحويل بين الإيجار العادي والساعات في صفحة عاملات الإيجار. */
class MaidRentalModeController extends Controller
{
    public function __construct(private RentalModeSwitcher $switcher)
    {
    }

    public function toHourly(Maid $maid): JsonResponse
    {
        $this->assertRentalMaid($maid);
        $maid = $this->switcher->toHourly($maid);

        return response()->json([
            'message' => __('rental_mode.success.to_hourly', ['name' => $maid->name]),
            'redirect' => route('admin.hourly-maids.show', $maid),
        ]);
    }

    public function toRegular(Maid $maid): JsonResponse
    {
        $this->assertRentalMaid($maid);
        $maid = $this->switcher->toRegular($maid);

        return response()->json([
            'message' => __('rental_mode.success.to_regular', ['name' => $maid->name]),
        ]);
    }

    private function assertRentalMaid(Maid $maid): void
    {
        abort_unless($maid->jobTypes()->where('job_types.id', RentalMaidQuery::rentalJobTypeId())->exists(), 404);
    }
}
