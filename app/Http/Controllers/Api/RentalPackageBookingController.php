<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\OrderAction;
use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Package;
use App\Models\ingaz\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Creates a rental request from a package without reserving a maid.
 * A responsible admin assigns a compatible maid from the rental-orders screen.
 */
class RentalPackageBookingController extends Controller
{
    public function store(Request $request, OrderAction $orderAction)
    {
        $data = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'address_id' => ['required', 'integer'],
            'deliver_maid' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'string', 'max:100'],
        ]);

        $userId = (int) auth()->id();

        $package = Package::query()
            ->visible()
            ->whereKey($data['package_id'])
            ->first();

        if (! $package) {
            return apiResponse(false, __('ingaz.No_packages_to_show'), null, 422);
        }

        $addressExists = UserAddress::query()
            ->whereKey($data['address_id'])
            ->where('user_id', $userId)
            ->exists();

        if (! $addressExists) {
            return apiResponse(false, 'العنوان المحدد غير موجود أو لا يخص المستخدم الحالي.', null, 422);
        }

        $order = DB::transaction(function () use ($request, $data, $orderAction) {
            $request->merge([
                'details_list' => [
                    'package_id' => (int) $data['package_id'],
                    'address_id' => (int) $data['address_id'],
                    'deliver_maid' => ! empty($data['deliver_maid']) ? 1 : 0,
                ],
            ]);

            return $orderAction->storeOrder([
                'maid_id' => null,
                'job_type_id' => 2,
                'payment_method' => $data['payment_method'] ?? 'none',
            ], $request);
        });

        return apiResponse(true, 'تم إرسال طلب باقة الإيجار بنجاح.', [
            'order' => $order->loadMissing([
                'details.package',
                'details.address',
                'user',
                'jobType',
            ]),
        ], 201);
    }
}
