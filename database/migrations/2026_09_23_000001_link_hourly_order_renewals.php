<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * ربط الطلب المجدَّد بأصله: العمود القديم renewed_from_id (فهرس بلا مفتاح أجنبي) يُعاد تسميته إلى
 * renewed_from_order_id بمفتاح أجنبي، بدل إنشاء عمود ثانٍ لنفس العلاقة. وتُسجَّل صلاحية تجديد طلب مجدَّد سابقًا.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('hourly_rental_orders', 'renewed_from_id')) {
            Schema::table('hourly_rental_orders', fn (Blueprint $t) => $t->renameColumn('renewed_from_id', 'renewed_from_order_id'));
        } elseif (!Schema::hasColumn('hourly_rental_orders', 'renewed_from_order_id')) {
            Schema::table('hourly_rental_orders', fn (Blueprint $t) => $t->unsignedBigInteger('renewed_from_order_id')->nullable());
        }
        // أي قيمة يتيمة (طلب أصل محذوف) تُفرَّغ حتى لا يفشل إنشاء المفتاح الأجنبي.
        // Read orphan IDs separately: MySQL forbids updating a table selected by its own subquery.
        DB::table('hourly_rental_orders as renewal')
            ->leftJoin('hourly_rental_orders as source', 'source.id', '=', 'renewal.renewed_from_order_id')
            ->whereNotNull('renewal.renewed_from_order_id')->whereNull('source.id')
            ->select('renewal.id')->chunkById(500, function ($orders) {
                DB::table('hourly_rental_orders')->whereIn('id', $orders->pluck('id'))
                    ->update(['renewed_from_order_id' => null]);
            }, 'renewal.id', 'id');
        // فهرس العمود القديم يبقى بعد إعادة التسمية؛ نستبدله بفهرس واحد باسم العلاقة الجديدة.
        $indexes = array_map('strtolower', array_keys(Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('hourly_rental_orders')));
        $foreignKeys = array_map(fn ($key) => strtolower($key->getName()), Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('hourly_rental_orders'));
        Schema::table('hourly_rental_orders', function (Blueprint $t) use ($indexes) {
            if (in_array('hourly_rental_orders_renewed_from_id_index', $indexes, true)) $t->dropIndex('hourly_rental_orders_renewed_from_id_index');
        });
        Schema::table('hourly_rental_orders', function (Blueprint $t) use ($indexes, $foreignKeys) {
            if (!in_array('hourly_orders_renewed_from_order_idx', $indexes, true)) $t->index('renewed_from_order_id', 'hourly_orders_renewed_from_order_idx');
            if (!in_array('hourly_orders_renewed_from_order_fk', $foreignKeys, true)) $t->foreign('renewed_from_order_id', 'hourly_orders_renewed_from_order_fk')->references('id')->on('hourly_rental_orders')->nullOnDelete();
        });

        if (Schema::hasTable('permissions')) {
            Permission::firstOrCreate(['name' => 'hourly_orders_renew_again', 'guard_name' => 'admin'], [
                'group_name' => 'hourly_orders', 'section_name' => 'hourly_rental', 'display_name' => 'تجديد طلب سبق تجديده',
                'description' => 'إنشاء تجديد ثانٍ لطلب له تجديد قائم، مع تسجيل ذلك في سجلات الطلبين',
            ]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::table('hourly_rental_orders', function (Blueprint $t) {
            $t->dropForeign('hourly_orders_renewed_from_order_fk');
            $t->dropIndex('hourly_orders_renewed_from_order_idx');
        });
        Schema::table('hourly_rental_orders', fn (Blueprint $t) => $t->renameColumn('renewed_from_order_id', 'renewed_from_id'));
        if (Schema::hasTable('permissions')) {
            Permission::where('name', 'hourly_orders_renew_again')->where('guard_name', 'admin')->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
