<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;

class AssignRecruitmentContractsPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:recruitment-contracts-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تعيين جميع صلاحيات عقود الاستقدام (rec_contracts_) إلى دور Administrator فقط.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 جاري تعيين صلاحيات عقود الاستقدام لدور Administrator...');

        $permissions = Permission::where('name', 'like', 'rec_contracts_%')->get();
        $administratorRole = Role::where('name', 'Administrator')->first();

        if (!$administratorRole) {
            $this->error('❌ لم يتم العثور على دور Administrator');
            return Command::FAILURE;
        }

        $administratorRole->givePermissionTo($permissions);

        $this->info('✅ تم تعيين جميع صلاحيات عقود الاستقدام لدور Administrator بنجاح!');
        return Command::SUCCESS;
    }
}
