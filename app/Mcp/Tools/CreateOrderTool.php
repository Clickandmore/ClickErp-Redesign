<?php

namespace App\Mcp\Tools;

use App\Http\Actions\ingaz\OrderAction;
use App\Mcp\McpToolException;
use App\Mcp\Tools\Concerns\ScopesCustomersToBranch;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\Package;
use App\Models\ingaz\User;
use App\Services\OrderMaidReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * طلب جديد لعميل موجود على عاملة متاحة، عبر OrderAction::storeOrder نفسه (رمز الطلب، مدة الحجز، الإشعارات).
 * الطلب يحجز العاملة ومسؤوله الموظف، وOrderMaidReservationService يفك الحجز عند حذفه.
 */
class CreateOrderTool extends McpWriteTool
{
    use ScopesCustomersToBranch;

    private const JOB_TYPES = ['recruitment' => 1, 'rental' => 2, 'immediate' => 3];

    private const LABELS = ['recruitment' => 'استقدام', 'rental' => 'إيجار عمالة', 'immediate' => 'استلام فوري'];

    public function name(): string
    {
        return 'create_order';
    }

    public function description(): string
    {
        return 'إنشاء طلب جديد (استقدام أو إيجار أو استلام فوري) لعميل موجود على عاملة متاحة. الإيجار يحتاج package_id من form_options. '
            . 'تعمل بخطوتين: معاينة ثم موافقة المستخدم. Create an order for an existing customer and an available worker (preview, then approval).';
    }

    protected function fieldsSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'integer', 'description' => 'معرّف العميل من search_customers'],
                'maid_id' => ['type' => 'integer', 'description' => 'معرّف عاملة متاحة من search_maids'],
                'order_type' => ['type' => 'string', 'enum' => array_keys(self::JOB_TYPES)],
                'package_id' => ['type' => 'integer', 'description' => 'مطلوب للإيجار فقط، من form_options بـ form=order'],
            ],
            'required' => ['customer_id', 'maid_id', 'order_type'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'orders.create';
    }

    public function category(): string
    {
        return 'orders';
    }

    protected function prepare(array $arguments, Admin $admin): array
    {
        $data = Validator::make($arguments, [
            'customer_id' => ['required', 'integer'],
            'maid_id' => ['required', 'integer'],
            'order_type' => ['required', Rule::in(array_keys(self::JOB_TYPES))],
            'package_id' => ['required_if:order_type,rental', 'nullable', 'integer'],
        ])->validate();

        $customer = $this->customer((int) $data['customer_id'], $admin);
        $maid = Maid::query()->with('nationality')->find((int) $data['maid_id']);

        if (!$maid) {
            throw new McpToolException('العاملة غير موجودة أو خارج نطاق فرعك.');
        }

        $this->assertMaidIsBookable($maid, $data['order_type']);
        $package = $data['order_type'] === 'rental' ? $this->package((int) $data['package_id'], $maid) : null;

        return [
            'data' => $data,
            'preview' => array_filter([
                'order_type' => self::LABELS[$data['order_type']],
                'customer' => $customer->name,
                'customer_phone' => $this->mask($customer->phone),
                'maid' => $maid->name,
                'nationality' => $maid->nationality?->title,
                'package' => $package?->title,
                'responsible' => $admin->name,
                'will_also' => 'حجز العاملة بعد تأكيد الطلب، وإرسال إشعار للعميل ورسالة نصية للموظف المسؤول.',
            ]),
        ];
    }

    protected function commit(array $data, Admin $admin): array
    {
        $customer = $this->customer((int) $data['customer_id'], $admin);
        $request = new Request(['details_list' => array_filter([
            'admin_id' => $admin->id,
            'package_id' => $data['package_id'] ?? null,
        ])]);

        $order = DB::transaction(function () use ($data, $customer, $request, $admin) {
            // القفل يمنع حجز العاملة نفسها من طلبين متزامنين.
            $maid = Maid::query()->lockForUpdate()->find((int) $data['maid_id']);

            if (!$maid) {
                throw new McpToolException('العاملة غير موجودة أو خارج نطاق فرعك.');
            }

            $this->assertMaidIsBookable($maid, $data['order_type']);

            $order = app(OrderAction::class)->storeOrder([
                'user_id' => $customer->id,
                'maid_id' => $maid->id,
                'job_type_id' => self::JOB_TYPES[$data['order_type']],
                'payment_method' => 'cash',
                'customer_id' => $admin->id,
            ], $request);

            $maid->update(['status' => 'reserved', 'available' => false]);

            return $order;
        });

        return [
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'url' => $this->link('orders.show', $order->id),
        ];
    }

    private function customer(int $id, Admin $admin): User
    {
        $query = User::query()->whereKey($id)->where('is_blocked', false);
        $this->applyCustomerBranchScope($query, $admin);
        $customer = $query->first();

        if (!$customer) {
            throw new McpToolException('العميل غير موجود أو محظور أو خارج نطاق فرعك.');
        }

        return $customer;
    }

    private function assertMaidIsBookable(Maid $maid, string $orderType): void
    {
        if ($maid->status !== 'available' || !$maid->available) {
            throw new McpToolException('العاملة غير متاحة حالياً.');
        }

        if (app(OrderMaidReservationService::class)->hasOtherCommitment($maid->id, 0)) {
            throw new McpToolException('العاملة مرتبطة بطلب أو عقد آخر.');
        }

        if (!$maid->jobTypes()->where('job_types.id', self::JOB_TYPES[$orderType])->exists()) {
            throw new McpToolException('هذه العاملة غير مخصصة لهذا النوع من الطلبات: ' . self::LABELS[$orderType] . '.');
        }
    }

    private function package(int $id, Maid $maid): Package
    {
        $package = Package::query()->visible()->forNationality($maid->nationality_id)->whereKey($id)->first();

        if (!$package) {
            throw new McpToolException('الباقة غير موجودة أو لا تناسب جنسية العاملة. راجع form_options بـ form=order.');
        }

        return $package;
    }
}
