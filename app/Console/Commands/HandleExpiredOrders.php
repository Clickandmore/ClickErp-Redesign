<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HandleExpiredOrders extends Command
{
    protected $signature = 'orders:handle-expired';
    protected $description = 'Compatibility alias for orders:cancel-expired';

    public function handle(): int
    {
        return $this->call('orders:cancel-expired');
    }
}
