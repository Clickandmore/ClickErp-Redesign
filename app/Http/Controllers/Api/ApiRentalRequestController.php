<?php

namespace App\Http\Controllers\Api;

use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\ingaz\OrderAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\Package;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use App\Support\RentalOrderSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * طلب إيجار قادم من الموقع الخارجي.
 *
 * يقبل العميل المسجّل (عبر التوكن) والعميل غير المسجّل (بالاسم ورقم الجوال)،
 * ويحترم وضع طلب الإيجار المختار في إعدادات لوحة التحكم:
 * - full       : باقة ثم عنوان ثم تأكيد.
 * - simplified : تأكيد مباشر بعد الاسم ورقم الجوال.
 *
 * مسار /client/eagar-request القديم لم يتغيّر، وما زال يخدم النسخ الحالية.
 */
class ApiRentalRequestController extends Controller
{
    public function store(Request $request, OrderAction $orderAction)
    {
        $isSimplified = RentalOrderSettings::usesSimplifiedOrdering();
        $user = $this->resolveAuthenticatedUser();

        $rules = [
            'maid_id' => ['required', 'integer', 'exists:maids,id'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'deliver_maid' => ['nullable', 'boolean'],
            'address_id' => ['nullable', 'integer'],
            'address' => ['nullable', 'array'],
            'address.title' => ['nullable', 'string', 'max:255'],
            'address.name' => ['nullable', 'string', 'max:255'],
            'address.phone' => ['nullable', 'string', 'max:50'],
            'address.address' => ['nullable', 'string', 'max:500'],
            'address.notes' => ['nullable', 'string', 'max:1000'],
        ];

        if (!$user) {
            $rules['name'] = ['required', 'string', 'min:2', 'max:255'];
            $rules['phone'] = ['required', 'regex:/^5\d{8}$/'];
        }

        if ($user) {
            $rules['address_id'][] = Rule::exists('user_addresses', 'id')
                ->where(fn ($query) => $query->where('user_id', $user->id));
        }

        $data = $request->validate($rules, [
            'maid_id.required' => __('ingaz.maid_id_is_required'),
            'maid_id.exists' => __('ingaz.maid_id_is_not_exists'),
            'package_id.required' => __('ingaz.No_packages_to_show'),
        ]);

        $maid = Maid::with(['jobTypes', 'nationality'])->find($data['maid_id']);

        if (!$maid || !$maid->jobTypes->contains(fn ($jobType) => (int) $jobType->id === 2)) {
            return apiResponse(false, __('ingaz.job_type_not_found'), null, 422);
        }

        $availablePackages = Package::query()
            ->visible()
            ->forNationality($maid->nationality_id);

        $package = null;

        if (!empty($data['package_id'])) {
            $package = (clone $availablePackages)->whereKey($data['package_id'])->first();

            if (!$package) {
                return apiResponse(false, __('ingaz.No_packages_to_show'), null, 422);
            }
        } elseif (!$isSimplified && (clone $availablePackages)->exists()) {
            // التسلسل الكامل يلزم اختيار باقة، إلا إذا لم تكن للجنسية باقات أصلاً.
            return apiResponse(false, __('ingaz.No_packages_to_show'), null, 422);
        }

        return DB::transaction(function () use ($data, $request, $maid, $package, $user, $orderAction, $isSimplified) {
            if (!$user) {
                $user = $this->resolveGuestUser($data['name'], $data['phone']);

                if ($user === null) {
                    return apiResponse(false, __('web.account_is_blocked'), null, 422);
                }
            }

            // السيرة تختفي من القوائم بمجرد حجزها، لكن رابطًا قديمًا قد يصل إلى هنا،
            // فيُمنع حجزها مرة أخرى مهما كان صاحب الطلب القائم.
            $inProgress = Order::query()
                ->where('maid_id', $maid->id)
                ->whereIn('status', [
                    OrderEnum::New->value,
                    OrderEnum::Contracted->value,
                    OrderEnum::UnderProcess->value,
                    OrderEnum::ContractedWithoutContract->value,
                ])
                ->exists();

            if ($inProgress) {
                return apiResponse(false, __('web.there_are_order_in_prograss'), null, 403);
            }

            // الوضع المبسط يتخطى العنوان تمامًا.
            $addressId = $isSimplified ? null : $this->resolveAddressId($data, $user);

            $request->merge([
                'details_list' => [
                    'package_id' => $package?->id,
                    'address_id' => $addressId,
                    'deliver_maid' => $request->boolean('deliver_maid') ? 1 : 0,
                ],
            ]);

            $order = $orderAction->storeOrder([
                'user_id' => $user->id,
                'maid_id' => $maid->id,
                'job_type_id' => 2,
                // طلب الموقع الخارجي غير مدفوع، والدفع يُسجَّل لاحقًا من لوحة التحكم.
                'payment_method' => 'cash',
            ], $request);

            return apiResponse(true, __('ingaz.The request for hiring workers was successful'), [
                'order' => $order->loadMissing(['details.package', 'details.address', 'maid.nationality', 'jobType']),
                'user_created' => $user->wasRecentlyCreated,
            ], 201);
        });
    }

    /**
     * صاحب الطلب عندما يرسل الموقع الخارجي توكن عميل مسجّل.
     * التوكن غير الصالح لا يُفشل الطلب، وإنما يعامَل العميل كزائر.
     */
    private function resolveAuthenticatedUser(): ?User
    {
        try {
            $user = auth('api')->user();
        } catch (\Throwable) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }

    /**
     * عميل الموقع الخارجي غير المسجّل: يُستخدم حسابه إن وُجد برقم الجوال، وإلا يُنشأ.
     * يرجع null إذا كان الحساب موقوفًا.
     */
    private function resolveGuestUser(string $name, string $phone): ?User
    {
        $user = User::query()->where('phone', $phone)->lockForUpdate()->first();

        if ($user && (int) $user->is_blocked === 1) {
            return null;
        }

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'phone' => $phone,
                'phone_code' => '+966',
                'password' => Hash::make(Str::random(40)),
                'register_from' => 'web',
            ]);
        }

        return $user;
    }

    /**
     * عنوان الاستلام: إمّا عنوان محفوظ للعميل أو عنوان يُدخله يدويًا فيُحفظ له.
     */
    private function resolveAddressId(array $data, User $user): ?int
    {
        if (!empty($data['address_id'])) {
            $address = UserAddress::query()
                ->whereKey($data['address_id'])
                ->where('user_id', $user->id)
                ->first();

            if ($address) {
                return (int) $address->id;
            }
        }

        $manual = array_filter($data['address'] ?? [], fn ($value) => filled($value));

        if (empty($manual['address'])) {
            return null;
        }

        $address = UserAddress::create([
            'user_id' => $user->id,
            'title' => $manual['title'] ?? __('ingaz.address'),
            'name' => $manual['name'] ?? $user->name,
            'phone' => $manual['phone'] ?? $user->phone,
            'address' => $manual['address'],
            'notes' => $manual['notes'] ?? null,
        ]);

        return (int) $address->id;
    }
}
