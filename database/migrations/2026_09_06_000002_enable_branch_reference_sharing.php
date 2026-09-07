<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnableBranchReferenceSharing extends Migration
{
    private array $permissions = [
        PermKeys::MAIDS_VIEW_ALL_BRANCHES => [
            'display_name' => 'عرض السير الذاتية في كل الفروع',
            'description' => 'استثناء يسمح بعرض السير الذاتية التابعة لكل الفروع',
            'group_name' => 'maids',
            'section_name' => 'clients_maids',
        ],
        PermKeys::OFFICES_VIEW_ALL_BRANCHES => [
            'display_name' => 'عرض المكاتب الخارجية في كل الفروع',
            'description' => 'استثناء يسمح بعرض المكاتب الخارجية التابعة لكل الفروع',
            'group_name' => 'rec_offices',
            'section_name' => 'maids_settings',
        ],
    ];

    public function up(): void
    {
        $this->addOfficeBranchColumn();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->deletePermissions();

        if (Schema::hasTable('recruitment_offices') && Schema::hasColumn('recruitment_offices', 'branch_id')) {
            Schema::table('recruitment_offices', function (Blueprint $table) {
                $table->dropForeign('recruitment_offices_branch_id_foreign');
                $table->dropIndex('recruitment_offices_branch_id_index');
                $table->dropColumn('branch_id');
            });
        }
    }

    private function addOfficeBranchColumn(): void
    {
        if (! Schema::hasTable('recruitment_offices') || Schema::hasColumn('recruitment_offices', 'branch_id')) {
            return;
        }

        Schema::table('recruitment_offices', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('id');
            $table->index('branch_id', 'recruitment_offices_branch_id_index');

            if (Schema::hasTable('branches')) {
                $table->foreign('branch_id', 'recruitment_offices_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            }
        });
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ($this->permissions as $name => $metadata) {
            $payload = ['created_at' => now(), 'updated_at' => now()];

            foreach ($metadata as $column => $value) {
                if (Schema::hasColumn('permissions', $column)) {
                    $payload[$column] = $value;
                }
            }

            if (Schema::hasColumn('permissions', 'is_active')) {
                $payload['is_active'] = true;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'admin'],
                $payload
            );
        }

        $this->assignToAdministrator();
    }

    private function assignToAdministrator(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleId = DB::table('roles')
            ->where('guard_name', 'admin')
            ->where('name', 'Administrator')
            ->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_keys($this->permissions))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    private function deletePermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_keys($this->permissions))
            ->pluck('id');

        if ($permissionIds->isNotEmpty() && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        if ($permissionIds->isNotEmpty() && Schema::hasTable('role_branch_permissions')) {
            DB::table('role_branch_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_keys($this->permissions))
            ->delete();
    }
}
