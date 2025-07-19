<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\ingaz\Maid;
use App\Enums\ingaz\OrderEnum;

class CreateTestOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test orders for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating test orders...');

        // Get first user and maid
        $user = User::first();
        $maid = Maid::first();

        if (!$user || !$maid) {
            $this->error('No users or maids found in database!');
            return;
        }

        // Create refused order
        $refusedOrder = Order::create([
            'user_id' => $user->id,
            'maid_id' => $maid->id,
            'order_code' => 'TEST001',
            'status' => OrderEnum::Refused->value,
            'job_type_id' => 1,
            'status_changed_at' => now(),
            'deadline_at' => now()->addHours(24)
        ]);

        // Create return order
        $returnOrder = Order::create([
            'user_id' => $user->id,
            'maid_id' => $maid->id,
            'order_code' => 'TEST002',
            'status' => OrderEnum::Return->value,
            'job_type_id' => 1,
            'status_changed_at' => now(),
            'deadline_at' => now()->addHours(24)
        ]);

        // Create and soft delete an order
        $deletedOrder = Order::create([
            'user_id' => $user->id,
            'maid_id' => $maid->id,
            'order_code' => 'TEST003',
            'status' => OrderEnum::New->value,
            'job_type_id' => 1,
            'status_changed_at' => now(),
            'deadline_at' => now()->addHours(24)
        ]);
        $deletedOrder->delete(); // Soft delete

        $this->info('Created 3 test orders:');
        $this->info('- Refused order: ' . $refusedOrder->order_code);
        $this->info('- Return order: ' . $returnOrder->order_code);
        $this->info('- Deleted order: ' . $deletedOrder->order_code);
    }
}
