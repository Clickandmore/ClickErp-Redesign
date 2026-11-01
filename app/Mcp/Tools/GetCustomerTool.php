<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Mcp\Tools\Concerns\ScopesCustomersToBranch;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Models\RentalContract;

/**
 * ملف العميل كاملاً في رد واحد. كل قسم بصلاحية شاشته، وما لا يملكه الموظف يُذكر في not_permitted.
 * User بلا BranchScoped فيمر بـ ScopesCustomersToBranch؛ العقود والفواتير والشكاوى عليها BranchScoped.
 */
class GetCustomerTool extends McpTool
{
    use ScopesCustomersToBranch;

    private const ITEMS = 10;

    public function name(): string
    {
        return 'get_customer';
    }

    public function description(): string
    {
        return 'ملف عميل واحد بالمعرّف: بياناته، عقود الاستقدام والإيجار، الفواتير وإجمالي غير المدفوع، والشكاوى. '
            . 'استخدم search_customers أولاً إن لم تعرف المعرّف. Full customer profile: contracts, invoices and complaints.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer', 'description' => 'معرّف العميل من search_customers']],
            'required' => ['id'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'clients.show';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = User::query()->whereKey((int) ($arguments['id'] ?? 0));
        $this->applyCustomerBranchScope($query, $admin);
        $customer = $query->with('branch')->first();

        if (!$customer) {
            throw new McpToolException('العميل غير موجود أو خارج نطاق فرعك.');
        }

        $result = [
            'id' => $customer->id,
            'url' => $this->link('clients.show', $customer->id),
            'name' => $customer->name,
            'phone' => $this->mask($customer->phone),
            'national_id' => $this->mask($customer->national_num),
            'city' => $customer->city,
            'status' => $customer->status,
            'branch' => $customer->branch?->name,
            'created_at' => $this->date($customer->created_at),
        ];
        $hidden = [];

        if (self::routeAllows($admin, 'RecruitmentContract.index')) {
            $result['recruitment_contracts'] = $this->recruitment($customer);
        } else {
            $hidden[] = 'recruitment_contracts';
        }

        if (self::routeAllows($admin, 'RentContract.index')) {
            $result['rental_contracts'] = $this->rentals($customer);
        } else {
            $hidden[] = 'rental_contracts';
        }

        if (self::routeAllows($admin, 'invoices.index')) {
            $result['invoices'] = $this->invoices($customer);
        } else {
            $hidden[] = 'invoices';
        }

        if (self::routeAllows($admin, 'complaints.index')) {
            $result['complaints'] = $this->complaints($customer);
        } else {
            $hidden[] = 'complaints';
        }

        return $result + ['not_permitted' => $hidden];
    }

    private function recruitment(User $customer): array
    {
        $query = Contract::query()->whereIn('type', ['recruitment', 'maid'])->where('user_id', $customer->id);

        return [
            'total' => (clone $query)->count(),
            'latest' => $query->with('maid')->latest('id')->limit(self::ITEMS)->get()->map(fn (Contract $contract) => [
                'id' => $contract->id,
                'url' => $this->link('RecruitmentContract.show', $contract->id),
                'contract_number' => $contract->arabia_order_number,
                'date' => $this->date($contract->contract_date),
                'status' => $contract->status,
                'maid' => $contract->maid?->name,
                'warranty_end' => $this->date($contract->warranty_end_date),
            ])->all(),
        ];
    }

    private function rentals(User $customer): array
    {
        $query = RentalContract::query()->where('user_id', $customer->id);

        return [
            'total' => (clone $query)->count(),
            'latest' => $query->with(['maid', 'info'])->latest('id')->limit(self::ITEMS)->get()->map(fn (RentalContract $contract) => [
                'id' => $contract->id,
                'url' => $this->link('RentContract.show', $contract->id, 'RentContract.index'),
                'contract_number' => $contract->contract_number,
                'status' => $contract->status,
                'status_label' => $contract->status_text,
                'maid' => $contract->maid?->name,
                'to' => $this->date($contract->info?->ingaz_to),
                'value' => $contract->info?->contract_value,
                'remaining' => $contract->info?->remaining_amount,
            ])->all(),
        ];
    }

    /** فواتير الاستقدام غالباً بلا client_id؛ العميل على العقد. */
    private function invoices(User $customer): array
    {
        $query = Invoice::query()->where(fn ($q) => $q->where('client_id', $customer->id)
            ->orWhereHas('contract', fn ($contract) => $contract->where('user_id', $customer->id)));
        $totals = (clone $query)->selectRaw('count(*) as invoices, coalesce(sum(total_due), 0) as due, coalesce(sum(case when is_paid = 1 then 0 else total_due end), 0) as unpaid')->first();

        return [
            'total' => (int) $totals->invoices,
            'total_due' => round((float) $totals->due, 2),
            'unpaid' => round((float) $totals->unpaid, 2),
            'currency' => 'SAR',
            'latest' => $query->latest('id')->limit(self::ITEMS)->get()->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'url' => $this->link('invoices.show', $invoice->id),
                'date' => $this->date($invoice->created_at),
                'total_due' => $invoice->total_due,
                'is_paid' => (bool) $invoice->is_paid,
            ])->all(),
        ];
    }

    private function complaints(User $customer): array
    {
        $query = Complaint::query()->where(fn ($q) => $q->where('user_id', $customer->id)->orWhere('related_user_id', $customer->id));

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->whereNotIn('status', ['resolved', 'rejected', 'closed'])->count(),
            'latest' => $query->latest('id')->limit(self::ITEMS)->get()->map(fn (Complaint $complaint) => [
                'id' => $complaint->id,
                'url' => $this->link('complaints.show', $complaint->id),
                'status' => $complaint->status,
                'priority' => $complaint->priority,
                'subject' => \Illuminate\Support\Str::limit((string) $this->maskText($complaint->subject ?: $complaint->message), 100),
            ])->all(),
        ];
    }
}
