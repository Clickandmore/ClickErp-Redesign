<?php

namespace App\Console\Commands;

use App\Support\SystemModuleManager;
use Illuminate\Console\Command;

class AdminDesignCommand extends Command
{
    protected $signature = 'admin:design {design? : classic or redesign}';

    protected $description = 'Show or switch the active admin panel design';

    public function handle(): int
    {
        $design = $this->argument('design');

        if ($design === null) {
            $this->info('Active admin design: ' . SystemModuleManager::adminDesign());

            return self::SUCCESS;
        }

        if (! in_array($design, ['classic', 'redesign'], true)) {
            $this->error('Invalid design. Allowed values: classic, redesign.');

            return self::INVALID;
        }

        if (! SystemModuleManager::updateAdminDesign($design)) {
            $this->error('Could not save the design setting. Run the database migrations first.');

            return self::FAILURE;
        }

        $this->info('Admin design switched to: ' . $design);

        return self::SUCCESS;
    }
}
