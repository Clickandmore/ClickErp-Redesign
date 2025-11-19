<?php

namespace App\Console\Commands;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\OrderDetail;
use App\Models\ingaz\Package;
use App\Models\ingaz\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssignMaidsDemoRelations extends Command
{
    protected $signature = 'ingaz:assign-maids-demo
        {--available=120 : Number of maids to mark as available}
        {--orders=40 : Number of maids to attach new demo orders}
        {--contracts=40 : Number of maids to attach demo contracts (uses created orders when possible)}
        {--dry-run : Simulate without writing to DB}';

    protected $description = 'Ensure a mix of maids: some available, others with demo orders and contracts for testing.';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Blocked in production.');
            return self::FAILURE;
        }

        $availableCount = (int) $this->option('available');
        $orderCount = (int) $this->option('orders');
        $contractCount = (int) $this->option('contracts');
        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Setting %d maids available, %d with demo orders, %d with demo contracts%s',
            $availableCount,
            $orderCount,
            $contractCount,
            $dryRun ? ' (dry-run)' : ''
        ));

        DB::transaction(function () use ($availableCount, $orderCount, $contractCount, $dryRun) {
            $this->markMaidsAvailable($availableCount, $dryRun);
            $orders = $this->createDemoOrders($orderCount, $dryRun);
            $this->createDemoContracts($contractCount, $orders, $dryRun);

            if ($dryRun) {
                DB::rollBack();
            }
        });

        $this->info($dryRun ? 'Dry-run done. No changes written.' : 'Demo relations created.');
        return self::SUCCESS;
    }

    private function markMaidsAvailable(int $count, bool $dryRun): void
    {
        $maids = Maid::orderBy('id')->limit($count)->get();
        foreach ($maids as $maid) {
            if (! $dryRun) {
                $maid->update([
                    'available' => true,
                    'available_at' => Carbon::now(),
                    'status' => Maid::STATUS_AVAILABLE['en'],
                    'is_show' => true,
                ]);
            }
        }
        $this->line("Marked {$maids->count()} maids as available.");
    }

    private function createDemoOrders(int $count, bool $dryRun)
    {
        $maids = Maid::orderBy('id')->limit($count)->get();
        if ($maids->isEmpty()) {
            $this->warn('No maids found to create orders.');
            return collect();
        }

        $user = User::orderBy('id')->first();
        if (! $user) {
            $this->warn('No users found to attach orders.');
            return collect();
        }

        $package = Package::orderBy('id')->first();
        if (! $package) {
            $this->warn('No packages found; orders will be created without details.');
        }

        $orders = collect();
        foreach ($maids as $maid) {
            $orderCode = 'DEMO-ORD-' . Str::padLeft((string) $maid->id, 6, '0');
            $status = Arr::random([
                OrderEnum::New->value,
                OrderEnum::UnderProcess->value,
                OrderEnum::Contracted->value,
            ]);
            $createdAt = Carbon::now()->subDays(rand(1, 15));

            if (! $dryRun) {
                $order = Order::updateOrCreate(
                    ['order_code' => $orderCode],
                    [
                        'maid_id' => $maid->id,
                        'user_id' => $user->id,
                        'payment_method' => 'cash',
                        'job_type_id' => $maid->jobTypes()->value('job_type_id') ?? 1,
                        'status' => $status,
                        'status_changed_at' => $createdAt->copy()->addHours(4),
                        'deadline_at' => in_array($status, [OrderEnum::New->value, OrderEnum::UnderProcess->value], true)
                            ? $createdAt->copy()->addDays(3)
                            : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt->copy()->addDay(),
                    ]
                );

                if ($package) {
                    OrderDetail::updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'package_id' => $package->id,
                            'address_id' => optional($user->addresses()->first())->id,
                        ]
                    );
                }

                $orders->push($order);
            }
        }

        $this->line($dryRun ? "Would create {$maids->count()} orders." : "Created/updated {$orders->count()} orders.");
        return $orders;
    }

    private function createDemoContracts(int $count, $orders, bool $dryRun): void
    {
        $orders = collect($orders)->take($count);
        if ($orders->isEmpty()) {
            $this->warn('No orders to attach contracts.');
            return;
        }

        foreach ($orders as $idx => $order) {
            $maid = $order->maid;
            $user = $order->user;
            if (! $maid || ! $user) {
                continue;
            }

            $contractDate = Carbon::now()->subDays(rand(1, 10));
            $msaned = 'DEMO-MS-' . Str::padLeft((string) $order->id, 5, '0');

            if (! $dryRun) {
                $contract = Contract::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'maid_id' => $maid->id,
                        'user_id' => $user->id,
                        'status' => ContractStatusEnum::Shelter->value,
                        'required_maid_nationality_id' => $maid->nationality_id,
                        'required_maid_religion_id' => $maid->religion_id,
                        'required_maid_age_min' => 25,
                        'required_maid_age_max' => 40,
                        'contract_date' => $contractDate,
                        'msaned_number' => $msaned,
                        'visa_number' => 'VS-' . $msaned,
                    ]
                );

                ContractInfo::updateOrCreate(
                    ['contract_id' => $contract->id],
                    [
                        'visa_number' => $contract->visa_number,
                        'visa_hijry_date' => $contractDate,
                        'visa_milad_date' => $contractDate,
                        'working_hours_num' => '8',
                        'contract_duration' => 12,
                        'contract_value' => 48000,
                        'ingaz_from' => $contractDate->copy()->addDays(2),
                        'ingaz_to' => $contractDate->copy()->addDays(40),
                        'shelter_id' => null,
                    ]
                );
            }
        }

        $this->line($dryRun ? "Would create {$orders->count()} contracts." : "Created/updated {$orders->count()} contracts.");
    }
}
